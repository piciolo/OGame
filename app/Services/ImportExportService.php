<?php

namespace OGame\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use OGame\Enums\InventoryCategory;
use OGame\Models\ImportExportHistory;
use OGame\Models\ImportExportItem;
use OGame\Models\ImportExportOffer;
use OGame\Models\Planet;
use OGame\Models\User;
use OGame\Models\UserItem;
use RuntimeException;

/**
 * Logica del Mercante "Import / Export":
 * - generazione offerta giornaliera (lazy, su accesso pagina)
 * - pagamento atomico in risorse (metal/crystal/deuterium/honor)
 * - cambio offerta via Materia Oscura (max 2/giorno)
 * - acquisto diretto via Materia Oscura (500 DM)
 * - rilascio item nell'inventario user_items (riuso sistema esistente)
 *
 * Reset giornaliero: 00:00 timezone server (Europe/Rome via config app).
 */
class ImportExportService
{
    /** Costo fisso "Prendi item" via DM (osservato live OGame). */
    public const TAKE_ITEM_DM_COST = 500;

    /** Massimo numero di "Cambia" per ciclo giornaliero. */
    public const MAX_CHANGES_PER_CYCLE = 2;

    /** Moltiplicatori risorsa → punti container (immutabili lato OGame). */
    public const MULT_METAL     = 1;
    public const MULT_CRYSTAL   = 1.5;
    public const MULT_DEUTERIUM = 3;
    public const MULT_HONOR     = 100;

    /**
     * Restituisce l'offerta corrente del giocatore, generandone una nuova se
     * scaduta o inesistente.
     */
    public function getOrCreateOffer(User $user): ImportExportOffer
    {
        return DB::transaction(function () use ($user) {
            // 1. Offerta pending non scaduta -> ritorna quella
            $offer = ImportExportOffer::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();
            if ($offer) {
                return $offer;
            }

            // 2. Offerta gia' consumata nel ciclo corrente (paid/taken_dm con
            //    expires_at futuro) -> ritorna quella per mostrare stato
            //    post-acquisto "Per oggi non ci sono altre offerte".
            $consumed = ImportExportOffer::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['paid', 'taken_dm'])
                ->where('expires_at', '>', now())
                ->orderByDesc('id')
                ->first();
            if ($consumed) {
                return $consumed;
            }

            // 3. Nessuna offerta valida -> marca scadute e genera nuova
            ImportExportOffer::query()
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->where('expires_at', '<=', now())
                ->update(['status' => 'expired']);

            return $this->rollNewOffer($user);
        });
    }

    /**
     * Pagamento in risorse. Verifica: somma input × moltiplicatori === offer.price.
     * Atomico: lock pianeta, sottrae risorse, crea UserItem, marca offer.
     *
     * @param array{metal:int,crystal:int,deuterium:int,honor:int} $payment
     */
    public function pay(User $user, ImportExportOffer $offer, Planet $sourcePlanet, array $payment): UserItem
    {
        return DB::transaction(function () use ($user, $offer, $sourcePlanet, $payment) {
            $offer = ImportExportOffer::query()->where('id', $offer->id)->lockForUpdate()->first();
            if (!$offer || !$offer->isPending()) {
                throw new RuntimeException('Offer not available.');
            }

            $total = $payment['metal'] * self::MULT_METAL
                   + $payment['crystal'] * self::MULT_CRYSTAL
                   + $payment['deuterium'] * self::MULT_DEUTERIUM
                   + $payment['honor'] * self::MULT_HONOR;

            if ((int) round($total) !== (int) $offer->price) {
                throw new RuntimeException('Payment total mismatch (expected ' . $offer->price . ', got ' . $total . ').');
            }

            $sourcePlanet = Planet::query()->where('id', $sourcePlanet->id)->lockForUpdate()->first();
            if (!$sourcePlanet || $sourcePlanet->user_id !== $user->id) {
                throw new RuntimeException('Invalid source planet.');
            }

            if ($payment['metal'] > $sourcePlanet->metal
                || $payment['crystal'] > $sourcePlanet->crystal
                || $payment['deuterium'] > $sourcePlanet->deuterium) {
                throw new RuntimeException('Insufficient planet resources.');
            }

            $user = User::query()->where('id', $user->id)->lockForUpdate()->first();
            if ($payment['honor'] > $user->honor_points) {
                throw new RuntimeException('Insufficient honor points.');
            }

            $sourcePlanet->metal     -= $payment['metal'];
            $sourcePlanet->crystal   -= $payment['crystal'];
            $sourcePlanet->deuterium -= $payment['deuterium'];
            $sourcePlanet->save();

            if ($payment['honor'] > 0) {
                $user->honor_points -= $payment['honor'];
                $user->save();
            }

            $userItem = $this->grantItemToUser($user, $offer);

            $offer->status      = 'paid';
            $offer->consumed_at = now();
            $offer->save();

            ImportExportHistory::create([
                'user_id'            => $user->id,
                'item_id'            => $offer->item_id,
                'acquisition_method' => 'pay_resources',
                'paid_metal'         => $payment['metal'],
                'paid_crystal'       => $payment['crystal'],
                'paid_deuterium'     => $payment['deuterium'],
                'paid_honor'         => $payment['honor'],
                'paid_dm'            => 0,
                'source_planet_id'   => $sourcePlanet->id,
                'source_body_type'   => 'planet',
            ]);

            return $userItem;
        });
    }

    /**
     * "Cambia": rolla un nuovo item per la stessa offerta, costa DM in base alla
     * rarità dell'item attuale. Max 2 cambi per ciclo.
     */
    public function change(User $user, ImportExportOffer $offer): ImportExportOffer
    {
        return DB::transaction(function () use ($user, $offer) {
            $offer = ImportExportOffer::query()->where('id', $offer->id)->lockForUpdate()->first();
            if (!$offer || !$offer->isPending()) {
                throw new RuntimeException('Offer not available.');
            }
            if ($offer->change_count >= self::MAX_CHANGES_PER_CYCLE) {
                throw new RuntimeException('Change limit reached for this cycle.');
            }

            $currentItem = ImportExportItem::query()->find($offer->item_id);
            $cost = (int) $currentItem->change_dm_cost;

            $user = User::query()->where('id', $user->id)->lockForUpdate()->first();
            if ($user->dark_matter < $cost) {
                throw new RuntimeException('Insufficient dark matter.');
            }
            $user->dark_matter -= $cost;
            $user->save();

            $newItem  = $this->rollItem();
            $newPrice = $this->calculatePrice($newItem, $user);

            $offer->item_id      = $newItem->id;
            $offer->price        = $newPrice;
            $offer->change_count = $offer->change_count + 1;
            $offer->revealed_at  = now();
            $offer->save();

            return $offer;
        });
    }

    /**
     * "Prendi item": skip pagamento risorse, costa 500 DM.
     */
    public function takeWithDm(User $user, ImportExportOffer $offer): UserItem
    {
        return DB::transaction(function () use ($user, $offer) {
            $offer = ImportExportOffer::query()->where('id', $offer->id)->lockForUpdate()->first();
            if (!$offer || !$offer->isPending()) {
                throw new RuntimeException('Offer not available.');
            }

            $user = User::query()->where('id', $user->id)->lockForUpdate()->first();
            if ($user->dark_matter < self::TAKE_ITEM_DM_COST) {
                throw new RuntimeException('Insufficient dark matter.');
            }
            $user->dark_matter -= self::TAKE_ITEM_DM_COST;
            $user->save();

            $userItem = $this->grantItemToUser($user, $offer);

            $offer->status      = 'taken_dm';
            $offer->consumed_at = now();
            $offer->save();

            ImportExportHistory::create([
                'user_id'            => $user->id,
                'item_id'            => $offer->item_id,
                'acquisition_method' => 'pay_dm',
                'paid_metal'         => 0,
                'paid_crystal'       => 0,
                'paid_deuterium'     => 0,
                'paid_honor'         => 0,
                'paid_dm'            => self::TAKE_ITEM_DM_COST,
                'source_planet_id'   => null,
                'source_body_type'   => null,
            ]);

            return $userItem;
        });
    }

    /**
     * Calcola i max input per riga in base al pianeta attuale (per la UI).
     *
     * @return array{metal:int,crystal:int,deuterium:int,honor:int}
     */
    public function calculateMaxInputs(ImportExportOffer $offer, Planet $planet, User $user): array
    {
        $price = (int) $offer->price;
        return [
            'metal'     => min((int) $planet->metal, (int) floor($price / self::MULT_METAL)),
            'crystal'   => min((int) $planet->crystal, (int) floor($price / self::MULT_CRYSTAL)),
            'deuterium' => min((int) $planet->deuterium, (int) floor($price / self::MULT_DEUTERIUM)),
            'honor'     => min((int) $user->honor_points, (int) floor($price / self::MULT_HONOR)),
        ];
    }

    /**
     * Crea una nuova offerta giornaliera per il giocatore con item rollato e prezzo
     * calcolato. Scadenza: prossimo midnight server time.
     */
    private function rollNewOffer(User $user): ImportExportOffer
    {
        $item  = $this->rollItem();
        $price = $this->calculatePrice($item, $user);

        return ImportExportOffer::create([
            'user_id'      => $user->id,
            'item_id'      => $item->id,
            'price'        => $price,
            'status'       => 'pending',
            'change_count' => 0,
            'revealed_at'  => now(),
            'expires_at'   => $this->nextDailyResetAt(),
        ]);
    }

    /**
     * Estrae un item dal catalogo usando i drop_weight (random pesato).
     */
    private function rollItem(): ImportExportItem
    {
        $items = ImportExportItem::query()->get(['id', 'drop_weight']);
        $sum   = (int) $items->sum('drop_weight');
        $r     = random_int(1, $sum);
        $acc   = 0;
        foreach ($items as $row) {
            $acc += (int) $row->drop_weight;
            if ($r <= $acc) {
                return ImportExportItem::query()->find($row->id);
            }
        }
        return ImportExportItem::query()->find($items->last()->id);
    }

    /**
     * Formula prezzo: base × multiplier × jitter.
     * - multiplier_min = 2.2 (calibrato sull'osservato live: Bronze 5577 a 0pt)
     * - multiplier scala con sqrt(points / 250_000)
     */
    private function calculatePrice(ImportExportItem $item, User $user): int
    {
        $points     = (int) ($user->total_points ?? 0);
        $multBase   = sqrt(max(0, $points) / 250_000);
        $multiplier = max(2.2, $multBase);
        $jitter     = mt_rand(85, 115) / 100.0;

        return max(500, (int) round($item->price_base * $multiplier * $jitter));
    }

    /**
     * Genera un UserItem nell'inventario del giocatore a partire dall'offer pagata.
     */
    private function grantItemToUser(User $user, ImportExportOffer $offer): UserItem
    {
        $item = ImportExportItem::query()->find($offer->item_id);

        $category = match ($item->category) {
            'accelerator'    => InventoryCategory::Construction->value,
            'resource_boost' => InventoryCategory::Resources->value,
            default          => InventoryCategory::Items->value,
        };

        $activationType = $item->category === 'accelerator' ? 'instant' : 'duration';

        return UserItem::create([
            'user_id'         => $user->id,
            'item_type'       => $item->type,
            'tier'            => $item->rarity,
            'category'        => $category,
            'activation_type' => $activationType,
            'payload'         => [
                'effect_value'     => $item->effect_value,
                'duration_seconds' => $item->duration_seconds,
                'item_ref'         => $item->ref,
                'source_offer_id'  => $offer->id,
            ],
            'status'          => 'available',
            'acquired_at'     => now(),
            'source'          => 'import_export',
            'source_ref'      => $offer->id,
        ]);
    }

    /**
     * Ritorna il prossimo midnight in timezone server (Europe/Rome via config).
     */
    private function nextDailyResetAt(): Carbon
    {
        $tz = config('app.timezone', 'Europe/Rome');
        return Carbon::now($tz)->addDay()->startOfDay()->utc();
    }
}

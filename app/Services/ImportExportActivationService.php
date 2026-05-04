<?php

namespace OGame\Services;

use Illuminate\Support\Facades\DB;
use OGame\Models\BuildingQueue;
use OGame\Models\Planet;
use OGame\Models\PlanetBoost;
use OGame\Models\ResearchQueue;
use OGame\Models\UnitQueue;
use OGame\Models\User;
use OGame\Models\UserItem;
use RuntimeException;

/**
 * Attivazione item Import/Export dall'inventario user_items.
 *
 * - Acceleratori (Kraken/Detroid/Newtron): istantanei, sottraggono effect_value
 *   secondi dal time_end del primo elemento attivo della coda corrispondente.
 * - Resource boosters (Metal/Crystal/Deuterium): durata 7 giorni, marcati come
 *   activated_at = now (resta status=activated, consumed_at NULL fino a scadenza).
 *
 * Letti come "buff attivi" da PlanetService::updateResourceProductionStats().
 */
class ImportExportActivationService
{
    /**
     * Attiva un UserItem. Per gli acceleratori serve target planet_id; per i
     * resource boosters serve target planet_id + per quale risorsa (derivata
     * dall'item_type).
     *
     * @param UserItem $item
     * @param User $user
     * @param int|null $targetPlanetId  Pianeta su cui applicare l'effetto.
     * @return UserItem  L'item aggiornato.
     */
    public function activate(UserItem $item, User $user, ?int $targetPlanetId = null): UserItem
    {
        if ($item->user_id !== $user->id) {
            throw new RuntimeException('Item does not belong to user.');
        }
        if ($item->source !== 'import_export') {
            throw new RuntimeException('Only import_export items handled here.');
        }
        if ($item->status !== 'available') {
            throw new RuntimeException('Item is not available for activation.');
        }

        return DB::transaction(function () use ($item, $user, $targetPlanetId) {
            $item = UserItem::query()->where('id', $item->id)->lockForUpdate()->first();
            if (!$item || $item->status !== 'available') {
                throw new RuntimeException('Item not available.');
            }

            if ($item->activation_type === 'instant') {
                $this->applyInstantAccelerator($item, $user, $targetPlanetId);
                $item->status       = 'consumed';
                $item->activated_at = now();
                $item->consumed_at  = now();
                $item->save();
                return $item;
            }

            // Resource booster (duration): crea PlanetBoost; PlanetService legge
            // i bonus attivi via getActiveBoostMultipliers() (sistema esistente).
            if (!$targetPlanetId) {
                throw new RuntimeException('Target planet required for resource booster.');
            }
            $planet = Planet::query()->where('id', $targetPlanetId)->where('user_id', $user->id)->first();
            if (!$planet) {
                throw new RuntimeException('Invalid target planet.');
            }

            $resource = match ($item->item_type) {
                'metal_booster'     => 'metal',
                'crystal_booster'   => 'crystal',
                'deuterium_booster' => 'deuterium',
                default             => throw new RuntimeException('Unknown booster type: ' . $item->item_type),
            };

            $duration = (int) ($item->payload['duration_seconds'] ?? 0);
            if ($duration <= 0) {
                throw new RuntimeException('Invalid booster duration.');
            }

            PlanetBoost::create([
                'planet_id'           => (int) $planet->id,
                'user_id'             => (int) $user->id,
                'resource'            => $resource,
                'percent_bonus'       => (int) $item->payload['effect_value'],
                'expires_at'          => now()->addSeconds($duration),
                'source_user_item_id' => (int) $item->id,
            ]);

            $item->status       = 'consumed';
            $item->activated_at = now();
            $item->consumed_at  = now();
            $item->save();

            return $item;
        });
    }

    /**
     * Applica un acceleratore al primo elemento attivo della coda corrispondente.
     */
    private function applyInstantAccelerator(UserItem $item, User $user, ?int $targetPlanetId): void
    {
        $seconds = (int) ($item->payload['effect_value'] ?? 0);
        if ($seconds <= 0) {
            throw new RuntimeException('Invalid accelerator effect_value.');
        }

        $type = $item->item_type;

        match ($type) {
            'kraken'  => $this->speedupBuildingQueue($user, $targetPlanetId, $seconds),
            'detroid' => $this->speedupUnitQueue($user, $targetPlanetId, $seconds),
            'newtron' => $this->speedupResearchQueue($user, $seconds),
            default   => throw new RuntimeException('Unknown accelerator type: ' . $type),
        };
    }

    private function speedupBuildingQueue(User $user, ?int $targetPlanetId, int $seconds): void
    {
        if (!$targetPlanetId) {
            throw new RuntimeException('Target planet required for Kraken.');
        }
        $planet = Planet::query()->where('id', $targetPlanetId)->where('user_id', $user->id)->first();
        if (!$planet) {
            throw new RuntimeException('Invalid target planet.');
        }
        $row = BuildingQueue::query()
            ->where('planet_id', $planet->id)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->first();
        if (!$row) {
            throw new RuntimeException('No active building in queue.');
        }
        $row->time_end = max((int) $row->time_start, (int) $row->time_end - $seconds);
        $row->save();
    }

    private function speedupUnitQueue(User $user, ?int $targetPlanetId, int $seconds): void
    {
        if (!$targetPlanetId) {
            throw new RuntimeException('Target planet required for Detroid.');
        }
        $planet = Planet::query()->where('id', $targetPlanetId)->where('user_id', $user->id)->first();
        if (!$planet) {
            throw new RuntimeException('Invalid target planet.');
        }
        $row = UnitQueue::query()
            ->where('planet_id', $planet->id)
            ->where('processed', 0)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->first();
        if (!$row) {
            throw new RuntimeException('No active unit in queue.');
        }
        $row->time_end = max((int) $row->time_start, (int) $row->time_end - $seconds);
        $row->save();
    }

    private function speedupResearchQueue(User $user, int $seconds): void
    {
        $planetIds = Planet::query()->where('user_id', $user->id)->pluck('id');
        $row = ResearchQueue::query()
            ->whereIn('planet_id', $planetIds)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->first();
        if (!$row) {
            throw new RuntimeException('No active research in queue.');
        }
        $row->time_end = max((int) $row->time_start, (int) $row->time_end - $seconds);
        $row->save();
    }

}

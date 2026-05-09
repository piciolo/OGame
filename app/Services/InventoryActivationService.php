<?php

namespace OGame\Services;

use Illuminate\Support\Facades\DB;
use OGame\Factories\PlanetServiceFactory;
use OGame\Models\BuildingQueue;
use OGame\Models\Enums\PlanetType;
use OGame\Models\PlanetBoost;
use OGame\Models\Planet;
use OGame\Models\ResearchQueue;
use OGame\Models\Resources;
use OGame\Models\UnitQueue;
use OGame\Models\User;
use OGame\Models\UserItem;
use RuntimeException;

/**
 * Applies the real game effect of an activated inventory item.
 *
 * Pipeline (called by ShopController::activate):
 *   1. lock the user's first available UserItem stack for the given (item_type, tier)
 *   2. validate the activation context (queue exists, planet ok, etc.)
 *   3. apply the effect (reduce queue / insert planet_boost / credit resources)
 *   4. mark the UserItem as consumed
 *
 * All in one DB transaction. If validation fails, nothing is consumed.
 *
 * Effect sources are OGame-official:
 * - KRAKEN/DETROID/NEWTRON tiers: Bronze -30m, Silver -2h, Gold -6h, Platinum -1d
 *   (residual time carries over to the next queued task)
 * - Amplifier metal/crystal/deuterium: Bronze 10%, Silver 20%, Gold 30%, Platinum 40%
 *   (default duration 7 days; same-tier same-resource = stack duration)
 * - Amplifier energy: Bronze 10%, Silver 20%, Gold 30%, Platinum 40%
 *   (only one energy amplifier active at a time — replaces previous)
 */
class InventoryActivationService
{
    /**
     * Returns the planet/moon type required to activate the given item, or null if
     * the item works on either context.
     *
     * Rules (verified vs OGame ufficiale):
     *   - amplifier_metal/crystal/deuterium/energy → planet only (no production on moons)
     *   - planet_fields → planet only
     *   - moon_fields   → moon only
     *   - all other items (boosters, resources_lot, slot timers) → either
     */
    private function getRequiredTargetType(string $itemType): PlanetType|null
    {
        if ($itemType === 'moon_fields') {
            return PlanetType::Moon;
        }
        if ($itemType === 'planet_fields' || str_starts_with($itemType, 'amplifier_')) {
            return PlanetType::Planet;
        }
        return null;
    }

    /**
     * Convert reduction shorthand ("30m", "2h", "6h", "1d") to seconds.
     */
    private function reductionToSeconds(string $shorthand): int
    {
        return match ($shorthand) {
            '30m' => 1800,
            '2h'  => 7200,
            '6h'  => 21600,
            '1d'  => 86400,
            default => 0,
        };
    }

    public function __construct(
        private readonly PlanetServiceFactory $planetServiceFactory,
    ) {
    }

    /**
     * @return array{ok:bool, code:string, item:?UserItem}
     */
    public function activate(User $user, string $itemType, string|null $tier, int $planetId): array
    {
        // Avatar profilo (acquistati dallo shop categoria "profilo"): non sono nel
        // registry inventory_items, hanno logica dedicata che NON consuma l'item.
        if ($itemType === 'profile_avatar') {
            return $this->activateProfileAvatar($user, $tier);
        }

        $registry = config('inventory_items');
        $key = $itemType . ':' . ((string) $tier);
        if (!isset($registry[$key])) {
            return ['ok' => false, 'code' => 'unknown_item', 'item' => null];
        }
        $def = $registry[$key];

        return DB::transaction(function () use ($user, $itemType, $tier, $planetId, $def) {
            /** @var UserItem|null $item */
            $item = UserItem::query()
                ->where('user_id', $user->id)
                ->where('item_type', $itemType)
                ->where('tier', $tier)
                ->where('status', 'available')
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if ($item === null) {
                return ['ok' => false, 'code' => 'not_found', 'item' => null];
            }

            // Resolve planet ownership
            $planet = Planet::find($planetId);
            if ($planet === null || (int) $planet->user_id !== (int) $user->id) {
                return ['ok' => false, 'code' => 'invalid_planet', 'item' => null];
            }

            // Validate planet/moon target requirement.
            // Examples: amplifier_energy can only be activated on a planet (no energy
            // production on moons); moon_fields can only be applied on a moon, etc.
            $required = $this->getRequiredTargetType($itemType);
            if ($required !== null && (int) $planet->planet_type !== $required->value) {
                return [
                    'ok' => false,
                    'code' => $required === PlanetType::Moon ? 'requires_moon' : 'requires_planet',
                    'item' => null,
                ];
            }

            // Dispatch by item_type
            try {
                $applied = match (true) {
                    $itemType === 'booster_kraken'   => $this->applyQueueReduction($planetId, BuildingQueue::class, $this->reductionFor($def, $item)),
                    $itemType === 'booster_detroid'  => $this->applyQueueReduction($planetId, UnitQueue::class, $this->reductionFor($def, $item)),
                    $itemType === 'booster_newtron'  => $this->applyResearchQueueReduction($user, $this->reductionFor($def, $item)),
                    str_starts_with($itemType, 'amplifier_') => $this->applyAmplifier($user, $planetId, $itemType, $item),
                    $itemType === 'resources_lot'    => $this->applyResourcesLot($user, $planetId, $tier, $item),
                    $itemType === 'planet_fields'    => $this->applyPermanentFields($planet, $item, isMoon: false),
                    $itemType === 'moon_fields'      => $this->applyPermanentFields($planet, $item, isMoon: true),
                    $itemType === 'expedition_slot'  => $this->applyTimedSlots($user, $planetId, 'expedition_slots', $item),
                    $itemType === 'fleet_slot'       => $this->applyTimedSlots($user, $planetId, 'fleet_slots', $item),
                    default => null,
                };
            } catch (RuntimeException $e) {
                return ['ok' => false, 'code' => $e->getMessage(), 'item' => null];
            }

            if ($applied === null) {
                return ['ok' => false, 'code' => 'not_supported', 'item' => null];
            }
            if ($applied === false) {
                return ['ok' => false, 'code' => 'no_effect_target', 'item' => null];
            }

            $item->status = 'consumed';
            $item->consumed_at = now();
            $item->activated_at = now();
            $item->save();

            return ['ok' => true, 'code' => 'activated', 'item' => $item];
        }, 3);
    }

    /**
     * Avatar profilo dallo shop: applica al user (users.profile_avatar = "shop:<id>")
     * MA NON consuma l'item — resta nell'inventario per essere riapplicato/disattivato.
     * `activated_at` viene aggiornato a "now()" come marker dell'ultima attivazione.
     *
     * @return array{ok:bool, code:string, item:?UserItem}
     */
    private function activateProfileAvatar(User $user, string|null $tier): array
    {
        return DB::transaction(function () use ($user, $tier) {
            /** @var UserItem|null $item */
            $item = UserItem::query()
                ->where('user_id', $user->id)
                ->where('item_type', 'profile_avatar')
                ->where('tier', $tier)
                ->where('status', 'available')
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if ($item === null) {
                return ['ok' => false, 'code' => 'not_found', 'item' => null];
            }

            // Toggle: se l'avatar è già quello attivo per l'user → disattiva
            // (profile_avatar = null), altrimenti applica.
            $avatarRef = 'shop:' . (int) $item->source_ref;
            $isAlreadyActive = ($user->profile_avatar === $avatarRef);

            $user->profile_avatar = $isAlreadyActive ? null : $avatarRef;
            $user->save();

            // Marker di ultima attivazione (non consume in nessun caso).
            $item->activated_at = now();
            $item->save();

            return [
                'ok' => true,
                'code' => $isAlreadyActive ? 'deactivated' : 'activated',
                'item' => $item,
            ];
        }, 3);
    }

    /**
     * @param array<string,mixed> $def
     */
    private function reductionFor(array $def, UserItem $item): int
    {
        // Prefer payload->duration_seconds if present (auctioneer-granted boosters store the precise reduction)
        $payload = (array) ($item->payload ?? []);
        if (isset($payload['duration_seconds']) && (int) $payload['duration_seconds'] > 0) {
            return (int) $payload['duration_seconds'];
        }
        $template = (array) ($def['payload_template'] ?? []);
        $shorthand = (string) ($template['reduction'] ?? '');
        return $this->reductionToSeconds($shorthand);
    }

    /**
     * Reduce time_end of the running queue task on this planet by N seconds.
     * Residual cascades to the next queued task.
     *
     * Returns true on apply, false if no eligible task.
     */
    private function applyQueueReduction(int $planetId, string $modelClass, int $seconds): bool
    {
        if ($seconds <= 0) {
            return false;
        }

        /** @var array<int, BuildingQueue|UnitQueue> $items */
        $items = $modelClass::query()
            ->where('planet_id', $planetId)
            ->where('processed', 0)
            ->where('canceled', 0)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get()
            ->all();

        if (empty($items)) {
            return false;
        }

        $remaining = $seconds;
        $now = (int) now()->timestamp;

        foreach ($items as $task) {
            if ($remaining <= 0) {
                break;
            }
            if ((int) $task->time_start === 0) {
                // Not started yet — reduce its duration only (will start later naturally)
                $taskRemaining = (int) $task->time_duration;
                $cut = min($remaining, $taskRemaining);
                $task->time_duration = max(1, $taskRemaining - $cut);
                $task->save();
                $remaining -= $cut;
                continue;
            }
            // Currently running
            $taskRemaining = (int) $task->time_end - $now;
            if ($taskRemaining <= 0) {
                continue;
            }
            $cut = min($remaining, $taskRemaining);
            $task->time_end = (int) $task->time_end - $cut;
            $task->save();
            $remaining -= $cut;
        }

        return true;
    }

    /**
     * Research queue is per-user (one global queue across planets in standard OGame).
     */
    private function applyResearchQueueReduction(User $user, int $seconds): bool
    {
        if ($seconds <= 0) {
            return false;
        }

        $tasks = ResearchQueue::query()
            ->whereIn('planet_id', Planet::query()->where('user_id', $user->id)->pluck('id'))
            ->where('processed', 0)
            ->where('canceled', 0)
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get()
            ->all();

        if (empty($tasks)) {
            return false;
        }

        $remaining = $seconds;
        $now = (int) now()->timestamp;
        foreach ($tasks as $task) {
            if ($remaining <= 0) {
                break;
            }
            if ((int) $task->time_start === 0) {
                $taskRemaining = (int) $task->time_duration;
                $cut = min($remaining, $taskRemaining);
                $task->time_duration = max(1, $taskRemaining - $cut);
                $task->save();
                $remaining -= $cut;
                continue;
            }
            $taskRemaining = (int) $task->time_end - $now;
            if ($taskRemaining <= 0) {
                continue;
            }
            $cut = min($remaining, $taskRemaining);
            $task->time_end = (int) $task->time_end - $cut;
            $task->save();
            $remaining -= $cut;
        }
        return true;
    }

    /**
     * INSERT a planet_boosts row for an amplifier.
     * Energy: only one active at a time (delete previous active).
     * Same-tier same-resource: extend duration (OGame rule).
     */
    private function applyAmplifier(User $user, int $planetId, string $itemType, UserItem $item): bool
    {
        $resource = match ($itemType) {
            'amplifier_metal'     => 'metal',
            'amplifier_crystal'   => 'crystal',
            'amplifier_deuterium' => 'deuterium',
            'amplifier_energy'    => 'energy',
            default => null,
        };
        if ($resource === null) {
            return false;
        }

        $payload = (array) ($item->payload ?? []);
        $percent = (int) ($payload['percent'] ?? 0);
        $duration = (int) ($payload['duration_seconds'] ?? 604800); // default 7d
        if ($percent <= 0 || $duration <= 0) {
            return false;
        }

        // Energy: delete previously active energy boosts (only one at a time)
        if ($resource === 'energy') {
            PlanetBoost::query()
                ->where('planet_id', $planetId)
                ->where('resource', 'energy')
                ->where('expires_at', '>', now())
                ->delete();
        }

        // Stacking rule: same-tier same-resource amplifier extends duration
        // (OGame: 2× Metal Platinum 7d = 40% for 14d, NOT 80% for 7d)
        $existing = PlanetBoost::query()
            ->where('planet_id', $planetId)
            ->where('resource', $resource)
            ->where('percent_bonus', $percent)
            ->where('expires_at', '>', now())
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            $existing->expires_at = $existing->expires_at->copy()->addSeconds($duration);
            $existing->save();
            return true;
        }

        PlanetBoost::create([
            'planet_id' => $planetId,
            'user_id' => $user->id,
            'resource' => $resource,
            'percent_bonus' => $percent,
            'expires_at' => now()->addSeconds($duration),
            'source_user_item_id' => $item->id,
        ]);
        return true;
    }

    /**
     * Credit resources to the planet from a "Pacchetto Risorse" lot.
     * Per OGame: full package = +24h production summed across all player's planets,
     * delivered to chosen planet's deposits, capped at storage.
     * Here we use the auctioneer payload (metal/crystal/deuterium) directly,
     * since those values are already what the auctioneer/shop awarded.
     */
    private function applyResourcesLot(User $user, int $planetId, string|null $tier, UserItem $item): bool
    {
        $payload = (array) ($item->payload ?? []);
        $metal = (int) ($payload['metal'] ?? 0);
        $crystal = (int) ($payload['crystal'] ?? 0);
        $deuterium = (int) ($payload['deuterium'] ?? 0);

        if ($metal === 0 && $crystal === 0 && $deuterium === 0) {
            return false;
        }

        $service = $this->planetServiceFactory->make($planetId, true);
        if ($service === null) {
            throw new RuntimeException('invalid_planet');
        }

        $service->addResources(new Resources($metal, $crystal, $deuterium, 0), true);
        return true;
    }

    /**
     * Permanent +N fields on the chosen planet (Spazi Pianeta) or moon (Campi Lunari).
     * The target row's `field_max` column is incremented atomically.
     *
     * Tiers (verified OGame ufficiale):
     *   planet — Bronze +4, Silver +9, Gold +15, Platinum +20
     *   moon   — Bronze +2, Silver +4, Gold +6,  Platinum +8
     */
    private function applyPermanentFields(Planet $planet, UserItem $item, bool $isMoon): bool
    {
        $payload = (array) ($item->payload ?? []);
        $fields = (int) ($payload['fields'] ?? 0);
        if ($fields <= 0) {
            return false;
        }
        // Defensive double-check: outer activate() already validates target type via
        // getRequiredTargetType(), but keep this guard so direct callers stay safe.
        // Planet::planet_type is an int (1=Planet, 3=Moon).
        if ($isMoon && (int) $planet->planet_type !== PlanetType::Moon->value) {
            return false;
        }
        if (!$isMoon && (int) $planet->planet_type !== PlanetType::Planet->value) {
            return false;
        }

        $planet->field_max = (int) $planet->field_max + $fields;
        $planet->save();
        return true;
    }

    /**
     * Timed +N expedition_slots / fleet_slots stored as a PlanetBoost row keyed by a
     * non-resource string (resource = 'expedition_slots' | 'fleet_slots'). The
     * downstream Fleet/Expedition services sum percent_bonus from active rows
     * to compute the user's effective slot cap.
     *
     * Tiers (verified OGame ufficiale): Bronze +1, Silver +2, Gold +3 (default 7d).
     */
    private function applyTimedSlots(User $user, int $planetId, string $resource, UserItem $item): bool
    {
        $payload = (array) ($item->payload ?? []);
        $slots = (int) ($payload['slots'] ?? 0);
        $duration = (int) ($payload['duration_seconds'] ?? 604800);
        if ($slots <= 0 || $duration <= 0) {
            return false;
        }

        // Same-tier same-resource: extend duration (consistent with amplifier rule)
        $existing = PlanetBoost::query()
            ->where('planet_id', $planetId)
            ->where('resource', $resource)
            ->where('percent_bonus', $slots)
            ->where('expires_at', '>', now())
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            $existing->expires_at = $existing->expires_at->copy()->addSeconds($duration);
            $existing->save();
            return true;
        }

        PlanetBoost::create([
            'planet_id' => $planetId,
            'user_id' => $user->id,
            'resource' => $resource,
            'percent_bonus' => $slots,
            'expires_at' => now()->addSeconds($duration),
            'source_user_item_id' => $item->id,
        ]);
        return true;
    }
}

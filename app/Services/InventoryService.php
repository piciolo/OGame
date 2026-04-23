<?php

namespace OGame\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OGame\Enums\AuctionLotType;
use OGame\Enums\InventoryCategory;
use OGame\Models\Auction;
use OGame\Models\User;
use OGame\Models\UserItem;

class InventoryService
{
    /**
     * Resolve the registry key for an auction lot.
     * Returns null for lots that should not go into inventory (dark matter, ships).
     */
    public function registryKeyForAuction(Auction $auction): ?string
    {
        $tier = $auction->tier?->value;

        return match ($auction->lot_type) {
            AuctionLotType::BoosterKraken   => 'booster_kraken:' . $tier,
            AuctionLotType::BoosterNewtron  => 'booster_newtron:' . $tier,
            AuctionLotType::BoosterDetroid  => 'booster_detroid:' . $tier,
            AuctionLotType::ResourceBoost   => $this->amplifierKey($auction),
            AuctionLotType::Resources       => 'resources_lot:' . $tier,
            default                         => null,
        };
    }

    private function amplifierKey(Auction $auction): ?string
    {
        $payload = (array) $auction->lot_payload;
        $family = $payload['amplifier_family'] ?? null; // metal | crystal | deuterium | energy
        $tier = $auction->tier?->value;
        if ($family === null || $tier === null) {
            return null;
        }
        return 'amplifier_' . $family . ':' . $tier;
    }

    /**
     * Grant the auction prize into the winner's inventory (if applicable).
     * Dark matter and ship lots bypass inventory — handled by the auctioneer directly.
     */
    public function grantFromAuction(User $user, Auction $auction): ?UserItem
    {
        $key = $this->registryKeyForAuction($auction);
        if ($key === null) {
            return null;
        }

        $registry = config('inventory_items');
        if (!isset($registry[$key])) {
            Log::warning('InventoryService: no registry entry for auction key', [
                'auction_id' => $auction->id,
                'key' => $key,
            ]);
            return null;
        }

        $def = $registry[$key];

        // Merge registry payload template with the auction's specific payload (amounts for resource lots etc.)
        $payload = array_merge($def['payload_template'] ?? [], (array) $auction->lot_payload);

        return DB::transaction(function () use ($user, $def, $payload, $auction) {
            return UserItem::create([
                'user_id'         => $user->id,
                'item_type'       => $def['item_type'],
                'tier'            => $def['tier'],
                'category'        => $def['category']->value,
                'activation_type' => $def['activation_type'],
                'payload'         => $payload,
                'status'          => 'available',
                'acquired_at'     => now(),
                'source'          => 'auctioneer',
                'source_ref'      => (int) $auction->id,
            ]);
        });
    }

    /**
     * Aggregated inventory for the JS inventoryObj.
     * Returns a map keyed by stack ref (sha1 of item_type+tier) with:
     *   ref, item_type, tier, category (array of refs), amount, rarity, image, title, tooltip, payload
     */
    public function shopPayload(User $user): array
    {
        $rows = UserItem::query()
            ->where('user_id', $user->id)
            ->where('status', 'available')
            ->whereNull('consumed_at')
            ->get();

        $registry = config('inventory_items');
        $result = [];
        $orders = [];

        foreach ($rows as $row) {
            $key = $row->item_type . ':' . ($row->tier ?? '');
            $def = $registry[$key] ?? null;
            if ($def === null) {
                continue;
            }
            $ref = UserItem::refFor($row->item_type, $row->tier);

            if (!isset($result[$ref])) {
                $categoryRef = $def['category']->ref();
                $allRef = InventoryCategory::Items->ref(); // "tutto" bucket — we use Items as the all-bucket
                $result[$ref] = [
                    'ref' => $ref,
                    'item_type' => $row->item_type,
                    'tier' => $row->tier,
                    'category' => [$categoryRef, $allRef],
                    'amount' => 0,
                    'rarity' => $def['rarity'],
                    'imageLarge' => $def['image'],
                    'title' => $this->composeTooltip($def, $row->payload, 0),
                    'description_ext' => $def['description_key'],
                    'description_html' => __('t_shop_items.' . $def['description_key'], (array) ($row->payload ?? [])),
                    'canBeActivated' => true,
                    'canBeBoughtAndActivated' => false,
                    'activation_type' => $def['activation_type'],
                    'duration_seconds' => $def['duration_seconds'],
                    'payload' => $row->payload,
                    'first_item_id' => (int) $row->id,
                ];
            }
            $result[$ref]['amount']++;
        }

        // Recompute tooltip with final amount
        foreach ($result as $ref => &$item) {
            $key = $item['item_type'] . ':' . ($item['tier'] ?? '');
            $item['title'] = $this->composeTooltip($registry[$key], $item['payload'], $item['amount']);
        }
        unset($item);

        // Build item_orders keyed by category ref → { itemRef => index }
        foreach (InventoryCategory::cases() as $cat) {
            $orders[$cat->ref()] = [];
        }
        $idx = 0;
        foreach ($result as $ref => $item) {
            foreach ($item['category'] as $catRef) {
                $orders[$catRef][$ref] = $idx;
            }
            $idx++;
        }

        return [
            'items' => $result,
            'orders' => $orders,
        ];
    }

    /**
     * Build a map of [registryKey => count] for all available stacks of $user.
     * registryKey is the same "item_type:tier" format used in config/inventory_items.php.
     *
     * @return array<string,int>
     */
    public function countsByRegistryKey(User $user): array
    {
        $rows = UserItem::query()
            ->where('user_id', $user->id)
            ->where('status', 'available')
            ->whereNull('consumed_at')
            ->selectRaw('item_type, tier, COUNT(*) as c')
            ->groupBy('item_type', 'tier')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->item_type . ':' . ($r->tier ?? '')] = (int) $r->c;
        }
        return $out;
    }

    /**
     * Resolve a registry key from raw lot data (used to match auctioneer history
     * items against inventory counts without needing a loaded Auction model).
     */
    public function registryKeyForLot(string $lotType, string $tier, array $payload = []): ?string
    {
        return match ($lotType) {
            'booster_kraken'   => 'booster_kraken:' . $tier,
            'booster_newtron'  => 'booster_newtron:' . $tier,
            'booster_detroid'  => 'booster_detroid:' . $tier,
            'resource_boost'   => isset($payload['resource'])
                ? 'amplifier_' . $payload['resource'] . ':' . $tier
                : null,
            'resources'        => 'resources_lot:' . $tier,
            default            => null,
        };
    }

    /**
     * Count available items for a (item_type, tier) stack.
     */
    public function countStack(User $user, string $itemType, ?string $tier): int
    {
        return UserItem::query()
            ->where('user_id', $user->id)
            ->where('item_type', $itemType)
            ->where('tier', $tier)
            ->where('status', 'available')
            ->count();
    }

    /**
     * Consume (mark used) a single item from a stack. Returns true if one was consumed.
     * This is a stub for PR1 — real activation effects land in PR boost.
     */
    public function consumeOne(User $user, string $itemType, ?string $tier): ?UserItem
    {
        return DB::transaction(function () use ($user, $itemType, $tier) {
            $item = UserItem::query()
                ->where('user_id', $user->id)
                ->where('item_type', $itemType)
                ->where('tier', $tier)
                ->where('status', 'available')
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if ($item === null) {
                return null;
            }

            $item->status = 'consumed';
            $item->consumed_at = now();
            $item->activated_at = now();
            $item->save();

            return $item;
        });
    }

    private function composeTooltip(array $def, ?array $payload, int $amount): string
    {
        $title = __('t_shop_items.' . $def['title_key']);
        if (!empty($def['tier'])) {
            $title .= ' ' . __('t_shop_items.tier_' . $def['tier']);
        }
        $descParams = $payload ?? [];
        // Booster desc uses :reduction, amplifiers use :percent
        $desc = __('t_shop_items.' . $def['description_key'], $descParams);
        $duration = $def['duration_seconds'] > 0
            ? $this->humanizeDuration($def['duration_seconds'])
            : __('t_shop_items.duration_instant');
        $body = $desc . '<br /><br />'
              . __('t_shop_items.label_duration') . ': ' . $duration . '<br />'
              . __('t_shop_items.label_inventory') . ': ' . $amount;
        return $title . '|' . $body;
    }

    private function humanizeDuration(int $seconds): string
    {
        if ($seconds >= 86400 * 7) {
            return (int) floor($seconds / (86400 * 7)) . ' ' . __('t_shop_items.unit_week');
        }
        if ($seconds >= 86400) {
            return (int) floor($seconds / 86400) . ' ' . __('t_shop_items.unit_day');
        }
        if ($seconds >= 3600) {
            return (int) floor($seconds / 3600) . 'h';
        }
        return (int) floor($seconds / 60) . 'm';
    }
}

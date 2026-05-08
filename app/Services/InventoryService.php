<?php

namespace OGame\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OGame\Enums\AuctionLotType;
use OGame\Enums\InventoryCategory;
use OGame\Models\Auction;
use OGame\Models\PlanetBoost;
use OGame\Models\ShopItem;
use OGame\Models\User;
use OGame\Models\UserItem;

class InventoryService
{
    /**
     * Active (non-expired) boosts on a planet, formatted for the Overview buffBar
     * and Shop tab Inventario "Boost attivi" section.
     *
     * Each entry: { resource, percent, expires_at_ts, label, image, rarity }
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeBoostsForPlanet(int $planetId): array
    {
        $rows = PlanetBoost::query()
            ->where('planet_id', $planetId)
            ->where('expires_at', '>', now())
            ->orderBy('resource')
            ->orderByDesc('percent_bonus')
            ->get();

        // Pre-load matching ShopItem rows by name to enrich each boost with
        // description, price and inventory count (for tooltip/detail panel).
        $names = $rows->map(function ($b) {
            $tier = $this->tierFromPercent((int) $b->percent_bonus);
            $resourceLabel = match ($b->resource) {
                'metal'     => __('t_shop_items.amplifier_metal_title'),
                'crystal'   => __('t_shop_items.amplifier_crystal_title'),
                'deuterium' => __('t_shop_items.amplifier_deuterium_title'),
                'energy'    => __('t_shop_items.amplifier_energy_title'),
                default     => $b->resource,
            };
            return $resourceLabel . ' ' . __('t_shop_items.tier_' . $tier);
        })->unique()->values()->all();

        $shopItems = ShopItem::query()->whereIn('name', $names)->get()->keyBy('name');

        $userId = $rows->first()?->user_id;
        $invByItemTypeTier = $userId ? UserItem::query()
            ->where('user_id', $userId)
            ->where('status', 'available')
            ->whereNull('consumed_at')
            ->get()
            ->groupBy(fn ($u) => $u->item_type . ':' . ($u->tier ?? ''))
            ->map(fn ($g) => $g->count())
            ->all() : [];

        $result = [];
        foreach ($rows as $b) {
            $tier = $this->tierFromPercent((int) $b->percent_bonus);
            $imageFamily = 'amplifier_' . $b->resource;
            $imagePath = '/img/auctioneer/items/' . $imageFamily . '_' . $tier . '.png';
            $resourceLabel = match ($b->resource) {
                'metal'     => __('t_shop_items.amplifier_metal_title'),
                'crystal'   => __('t_shop_items.amplifier_crystal_title'),
                'deuterium' => __('t_shop_items.amplifier_deuterium_title'),
                'energy'    => __('t_shop_items.amplifier_energy_title'),
                default     => $b->resource,
            };
            $label = $resourceLabel . ' ' . __('t_shop_items.tier_' . $tier);

            $shop = $shopItems[$label] ?? null;
            $description = $shop?->description ?? '';
            $priceLabel = $shop?->price_label ?? '—';
            $durationLabel = $shop?->duration_label ?? '—';
            $inventoryKey = 'amplifier_' . $b->resource . ':' . $tier;
            $inventoryCount = (int) ($invByItemTypeTier[$inventoryKey] ?? 0);

            $result[] = [
                'id' => (int) $b->id,
                'resource' => $b->resource,
                'percent' => (int) $b->percent_bonus,
                'expires_at_ts' => (int) $b->expires_at->timestamp,
                'tier' => $tier,
                'rarity' => $this->rarityFromTier($tier),
                'label' => $label,
                'image_url' => $imagePath,
                'description' => $description,
                'price_label' => $priceLabel,
                'duration_label' => $durationLabel,
                'inventory_count' => $inventoryCount,
            ];
        }
        return $result;
    }

    private function tierFromPercent(int $percent): string
    {
        return match (true) {
            $percent >= 40 => 'platinum',
            $percent >= 30 => 'gold',
            $percent >= 20 => 'silver',
            default        => 'bronze',
        };
    }

    private function rarityFromTier(string $tier): string
    {
        return match ($tier) {
            'platinum' => 'epic',
            'gold'     => 'rare',
            'silver'   => 'uncommon',
            default    => 'common',
        };
    }

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
        $family = $payload['resource'] ?? $payload['amplifier_family'] ?? null; // metal | crystal | deuterium | energy
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

        // Preload ShopItem rows for any shop_item / profile_avatar UserItem
        // (source_ref -> ShopItem). I profile_avatar usano lo stesso lookup ma
        // con un ramo dedicato (item_type='profile_avatar', category=Profile).
        $shopIds = $rows->whereIn('item_type', ['shop_item', 'profile_avatar'])
            ->pluck('source_ref')->filter()->unique()->values()->all();
        $shopItems = [];
        if (!empty($shopIds)) {
            $shopItems = ShopItem::query()->whereIn('id', $shopIds)->get()->keyBy('id');
        }

        foreach ($rows as $row) {
            // Avatar profilo dallo shop: stesso pattern shop_item ma il `ref` dello
            // stack è basato su tier (= shop_item.id) per discriminare avatar diversi.
            // Differenza chiave: NON consumabile — il backend mantiene status='available'
            // anche dopo l'attivazione (vedi InventoryActivationService::activateProfileAvatar).
            // `is_active` = true se l'avatar attualmente selezionato dall'user è proprio
            // questo (users.profile_avatar == "shop:<id>"), così il pulsante può
            // mostrare "Disattiva" invece di "Attiva".
            if ($row->item_type === 'profile_avatar') {
                /** @var ShopItem|null $shop */
                $shop = $shopItems[$row->source_ref] ?? null;
                if ($shop === null) {
                    continue;
                }
                $ref = UserItem::refFor('profile_avatar', $row->tier);
                if (!isset($result[$ref])) {
                    $profileRef = InventoryCategory::Profile->ref();
                    $allRef = InventoryCategory::Items->ref();
                    $tx = __('t_shop_items_data.' . $shop->ref);
                    $hasTx = is_array($tx);
                    $tName = $hasTx && isset($tx['name']) ? $tx['name'] : $shop->name;
                    $tDesc = $hasTx && isset($tx['description']) ? $tx['description'] : ($shop->description ?? '');
                    $isActive = ($user->profile_avatar ?? '') === ('shop:' . (int) $row->source_ref);
                    $result[$ref] = [
                        'ref' => $ref,
                        'item_type' => 'profile_avatar',
                        'tier' => $row->tier,
                        'category' => [$profileRef, $allRef],
                        'amount' => 0,
                        'rarity' => $shop->rarity,
                        'imageLarge' => $shop->ref,
                        'image_override_url' => '/img/shop/' . $shop->image,
                        'title' => $tName . '|' . $this->cleanDescription($tDesc),
                        'description_ext' => $tDesc,
                        'description_html' => $tDesc,
                        'canBeActivated' => true,
                        'canBeBoughtAndActivated' => false,
                        'activation_type' => 'manual',
                        'duration_seconds' => 0,
                        'duration_label' => '',
                        'payload' => $row->payload,
                        'first_item_id' => (int) $row->id,
                        'shop_image' => $shop->image,
                        'is_active' => $isActive,
                    ];
                }
                $result[$ref]['amount']++;
                continue;
            }
            if ($row->item_type === 'shop_item') {
                /** @var ShopItem|null $shop */
                $shop = $shopItems[$row->source_ref] ?? null;
                if ($shop === null) {
                    continue;
                }
                $ref = UserItem::refFor('shop_item', $shop->ref);
                if (!isset($result[$ref])) {
                    $allRef = InventoryCategory::Items->ref();
                    $durSec = (int) ($shop->duration_seconds ?? 0);
                    // Per-ref translation lookup (same pattern as ShopService::itemToArray).
                    // Falls back to translated description for extended fields when missing.
                    $tx = __('t_shop_items_data.' . $shop->ref);
                    $hasTx = is_array($tx);
                    $tName = $hasTx && isset($tx['name']) ? $tx['name'] : $shop->name;
                    $tDesc = $hasTx && isset($tx['description']) ? $tx['description'] : $shop->description;
                    $tDur  = $hasTx && isset($tx['duration_label']) ? $tx['duration_label'] : $shop->duration_label;
                    $durationLabel = $tDur ?: ($durSec > 0 ? $this->humanizeDuration($durSec) : __('t_shop_items.duration_instant'));
                    // Long description: prefer translated extended_description, fall back to translated description, finally DB.
                    $longDesc = $hasTx
                        ? ($tx['extended_description'] ?? ($tx['description'] ?? (!empty($shop->extended_description) ? $shop->extended_description : $this->cleanDescription($shop->description))))
                        : (!empty($shop->extended_description) ? $shop->extended_description : $this->cleanDescription($shop->description));
                    // Runtime substitution for dynamic numeric placeholders (:metal/:crystal/:deuterium/:warning)
                    $longDesc = $this->substituteResourcePlaceholders($longDesc, $user, $shop);
                    $result[$ref] = [
                        'ref' => $ref,
                        'item_type' => 'shop_item',
                        'tier' => $shop->ref,
                        'category' => [$allRef],
                        'amount' => 0,
                        'rarity' => $shop->rarity,
                        'imageLarge' => $shop->ref,
                        'image_override_url' => '/img/shop/' . $shop->image,
                        'title' => $tName . '|' . $this->cleanDescription($tDesc)
                            . '<br /><br />'
                            . __('t_shop_items.label_duration') . ': ' . $durationLabel,
                        'description_ext' => $longDesc,
                        'description_html' => $longDesc,
                        'canBeActivated' => true,
                        'canBeBoughtAndActivated' => false,
                        'activation_type' => 'instant',
                        'duration_seconds' => $durSec,
                        'duration_label' => $durationLabel,
                        'payload' => $row->payload,
                        'first_item_id' => (int) $row->id,
                        'shop_image' => $shop->image,
                    ];
                }
                $result[$ref]['amount']++;
                continue;
            }

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

        // Recompute tooltip with final amount for registry items only
        // (skip shop_item e profile_avatar — il loro title viene già composto
        // direttamente dal nome del ShopItem nel branch dedicato).
        foreach ($result as $ref => &$item) {
            if (in_array($item['item_type'], ['shop_item', 'profile_avatar'], true)) {
                continue;
            }
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

    /**
     * Paginated catalog used by the Overview inventory overlay (#activeBuffDetails).
     *
     * Sourced from the full ShopItem catalog (130+ rows) ordered by sort_order
     * to match OGame ufficiale (8 tiles per slide). Owned count merges:
     *   - UserItem rows purchased from shop (source='shop', source_ref=shop_item.id)
     *   - UserItem rows granted by auctioneer (matched via booster_type + tier_key)
     *
     * @return array{pages: array<int, array<int, array<string, mixed>>>}
     */
    public function catalogForPlanet(User $user): array
    {
        $itemsPerSlide = 8;
        $imgDir = '/cdn/img/item-images/';

        // Filter to match OGame ufficiale overlay (~64 items):
        // exclude profile (avatar), class selection, and long-duration boosters
        // (30g/90g) which are duplicates of the 7g amplifiers in Risorse.
        // Also exclude Lifeform variants (mechanic not yet implemented).
        $shopItems = ShopItem::query()
            ->whereDoesntHave('categories', function ($q) {
                $q->whereIn('key', ['booster_30', 'booster_90', 'profilo', 'seleziona_classe']);
            })
            ->where('name', 'not like', '%(Forme di vita)%')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        // Build owned count maps from all available UserItem stacks
        $userItems = UserItem::query()
            ->where('user_id', $user->id)
            ->where('status', 'available')
            ->whereNull('consumed_at')
            ->get();

        $ownedByTypeTier = [];   // 'booster_kraken:gold' => N
        $ownedByShopRef = [];    // shop_item.id => N
        foreach ($userItems as $ui) {
            $key = $ui->item_type . ':' . ($ui->tier ?? '');
            $ownedByTypeTier[$key] = ($ownedByTypeTier[$key] ?? 0) + 1;
            if ($ui->source === 'shop' && $ui->source_ref) {
                $ownedByShopRef[(int) $ui->source_ref] = ($ownedByShopRef[(int) $ui->source_ref] ?? 0) + 1;
            }
        }

        $tiles = [];
        foreach ($shopItems as $si) {
            // Compute owned: shop-purchased + auctioneer-granted matching family/tier
            $owned = (int) ($ownedByShopRef[(int) $si->id] ?? 0);
            if (!empty($si->booster_type) && !empty($si->tier_key)) {
                $auctionKey = 'booster_' . $si->booster_type . ':' . $si->tier_key;
                $owned += (int) ($ownedByTypeTier[$auctionKey] ?? 0);
            }

            // Per-ref translation lookup (same pattern as ShopService::itemToArray):
            // pulls localized name/description/duration_label from
            // resources/lang/<loc>/t_shop_items_data.php when the locale provides
            // a translation for this ref. Falls back to DB Italian otherwise.
            $tx = __('t_shop_items_data.' . $si->ref);
            $hasTx = is_array($tx);
            $tTitle    = $hasTx && isset($tx['name'])           ? $tx['name']           : (string) $si->name;
            $tDesc     = $hasTx && isset($tx['description'])    ? $tx['description']    : (string) ($si->description ?? '');
            $tDuration = $hasTx && isset($tx['duration_label']) ? $tx['duration_label'] : (string) ($si->duration_label ?? __('t_shop_items.duration_instant'));

            $tiles[] = [
                'ref'              => (string) $si->ref,
                'item_type'        => 'shop_item',
                'tier'             => (string) ($si->tier_key ?? ''),
                'rarity'           => (string) ($si->rarity ?? 'common'),
                'image'            => (string) ($si->image ?? ''),
                'image_url'        => $imgDir . $si->image,
                'owned'            => $owned,
                'can_activate'     => $owned > 0,
                'duration_label'   => $tDuration,
                'duration_seconds' => (int) ($si->duration_seconds ?? 0),
                'title'            => $tTitle,
                'description_html' => $tDesc,
            ];
        }

        $pages = array_values(array_map('array_values', array_chunk($tiles, $itemsPerSlide)));
        if (empty($pages)) {
            $pages = [[]];
        }
        return ['pages' => $pages];
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

    private function cleanDescription(?string $raw): string
    {
        return (string) ($raw ?? '');
    }

    /**
     * Substitute runtime placeholders in OGame-native description templates:
     *   :metal       → daily metal production summed across user's planets, capped by free storage on current planet
     *   :crystal     → idem
     *   :deuterium   → idem
     *   :warning     → empty, OR the OGame `warningSign` span if any resource is at zero (storage full)
     *
     * Templates that don't contain these placeholders are returned unchanged.
     */
    public function substituteResourcePlaceholders(string $template, User $user, ShopItem $shop): string
    {
        if (!preg_match('/:metal|:crystal|:deuterium|:warning/', $template)) {
            return $template;
        }

        $planets = \OGame\Models\Planet::query()->where('user_id', $user->id)->get();
        if ($planets->isEmpty()) {
            return $template;
        }

        $dailyMetal = 0; $dailyCrystal = 0; $dailyDeuterium = 0;
        foreach ($planets as $p) {
            $dailyMetal     += (int) ($p->metal_production ?? 0) * 24;
            $dailyCrystal   += (int) ($p->crystal_production ?? 0) * 24;
            $dailyDeuterium += (int) ($p->deuterium_production ?? 0) * 24;
        }

        // Cap by free storage on the user's current planet (the activation target)
        $current = $planets->firstWhere('id', $user->planet_current) ?? $planets->first();
        $freeMetal     = max(0, (int) $current->metal_max - (int) $current->metal);
        $freeCrystal   = max(0, (int) $current->crystal_max - (int) $current->crystal);
        $freeDeuterium = max(0, (int) $current->deuterium_max - (int) $current->deuterium);
        $finalMetal     = min($dailyMetal,     $freeMetal);
        $finalCrystal   = min($dailyCrystal,   $freeCrystal);
        $finalDeuterium = min($dailyDeuterium, $freeDeuterium);

        $fmt = fn (int $n) => number_format($n, 0, ',', '.');

        $warning = '';
        if ($finalMetal === 0 || $finalCrystal === 0 || $finalDeuterium === 0) {
            $warning = '<br><span class="warningSign">' . __('t_shop_items.warning_storage_full') . '</span>';
        }

        return strtr($template, [
            ':metal'     => $fmt($finalMetal),
            ':crystal'   => $fmt($finalCrystal),
            ':deuterium' => $fmt($finalDeuterium),
            ':warning'   => $warning,
        ]);
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

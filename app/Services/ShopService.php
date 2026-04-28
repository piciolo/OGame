<?php

namespace OGame\Services;

use Illuminate\Support\Facades\DB;
use OGame\Models\ShopCategory;
use OGame\Models\ShopItem;
use OGame\Models\User;
use OGame\Models\UserItem;
use RuntimeException;

class ShopService
{
    public const ITEMS_PER_PAGE = 9;

    /** Categories never shown in the sidebar (items still appear via "tutto"). */
    private const HIDDEN_CATEGORIES = [];

    /** Virtual category key for the "tutto" (all items) tab. */
    public const ALL_CATEGORY_KEY = '_all';

    /**
     * Full catalog.
     * Shape: [
     *   'categories' => [ ['key','name','count','sort_order'], ... ] (sidebar list, includes "tutto"),
     *   'all_items' => [ ref => itemArray ],
     * ]
     * Pagination is done client-side (ITEMS_PER_PAGE).
     */
    public function catalog(): array
    {
        $categories = ShopCategory::query()->orderBy('sort_order')->get();
        // Lifeform boosters are temporarily disabled (mechanic not yet implemented).
        // Filter by name marker since `is_lifeform` column is not populated.
        $items = ShopItem::query()
            ->with('categories')
            ->where('name', 'not like', '%(Forme di vita)%')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $allItems = [];
        $byCat = [];
        foreach ($categories as $cat) {
            $byCat[$cat->key] = [];
        }
        $byCat[self::ALL_CATEGORY_KEY] = [];

        foreach ($items as $it) {
            $dto = $this->itemToArray($it);
            $allItems[$it->ref] = $dto;
            foreach ($it->categories as $cat) {
                $byCat[$cat->key][] = $dto;
            }
            $byCat[self::ALL_CATEGORY_KEY][] = $dto;
        }

        $counts = [];
        foreach ($byCat as $key => $list) {
            $counts[$key] = count($list);
        }

        // Build visible sidebar list — exclude hidden categories and append "tutto"
        // Translate category names from DB-stored keys to active locale via t_shop_items.
        $catKeyToTransKey = [
            'offerte_speciali' => 't_shop_items.category_special_offers',
            'seleziona_classe' => 't_shop_items.category_class_selection',
            'costruzione'      => 't_shop_items.category_construction',
            'risorse'          => 't_shop_items.category_resources',
            'booster_30'       => 't_shop_items.category_booster30',
            'booster_90'       => 't_shop_items.category_booster90',
            'profilo'          => 't_shop_items.category_profile',
        ];
        $catsOut = [];
        foreach ($categories as $cat) {
            if (in_array($cat->key, self::HIDDEN_CATEGORIES, true)) {
                continue;
            }
            $transKey = $catKeyToTransKey[$cat->key] ?? null;
            $name = $transKey ? __($transKey) : $cat->name;
            // Fallback: if translation key missing in current locale, __() returns
            // the key itself — keep DB name in that case.
            if ($transKey && $name === $transKey) {
                $name = $cat->name;
            }
            $catsOut[] = [
                'key' => $cat->key,
                'name' => $name,
                'count' => $counts[$cat->key] ?? 0,
                'sort_order' => $cat->sort_order,
            ];
        }
        $catsOut[] = [
            'key' => self::ALL_CATEGORY_KEY,
            'name' => __('t_shop_items.category_items'),
            'count' => $counts[self::ALL_CATEGORY_KEY] ?? 0,
            'sort_order' => 999,
        ];

        return [
            'categories' => $catsOut,
            'all_items' => $allItems,
        ];
    }

    public function itemToArray(ShopItem $it): array
    {
        $imgDir = '/cdn/img/item-images/';

        // Lookup per-ref translations from the active locale's t_shop_items_data
        // file (if present). Fall back to DB values otherwise. Keeps the runtime
        // backward-compatible while letting locales override item strings.
        $key = 't_shop_items_data.' . $it->ref;
        $tx = __($key);
        $hasTx = is_array($tx);
        $tName = $hasTx && isset($tx['name']) ? $tx['name'] : $it->name;
        $tDescription = $hasTx && isset($tx['description']) ? $tx['description'] : $it->description;
        $tExtended = $hasTx && isset($tx['extended_description']) ? $tx['extended_description'] : $it->extended_description;
        $tEffect = $hasTx && isset($tx['effect_description']) ? $tx['effect_description'] : $it->effect_description;
        $tRules = $hasTx && isset($tx['rules_description']) ? $tx['rules_description'] : $it->rules_description;
        $tDuration = $hasTx && isset($tx['duration_label']) ? $tx['duration_label'] : $it->duration_label;

        // Strip locale-specific currency abbreviation from price_label so the view
        // can append the translated dm_short. DB stores e.g. "350K MO" — we keep
        // only the numeric part "350K" and let the view add the translated suffix.
        $priceLabelStripped = preg_replace('/\s+\p{L}+\s*$/u', '', (string) $it->price_label);

        return [
            'id' => $it->id,
            'ref' => $it->ref,
            'name' => $tName,
            'description' => $tDescription,
            'extended_description' => $tExtended,
            'effect_description' => $tEffect,
            'rules_description' => $tRules,
            'price_dm' => $it->price_dm,
            'price_dm_original' => $it->price_dm_original,
            'price_label' => $priceLabelStripped,
            'price_label_original' => $it->price_label_original,
            'duration_seconds' => $it->duration_seconds,
            'duration_label' => $tDuration,
            'rarity' => $it->rarity,
            'image' => $it->image,
            'image_fallback' => $it->image_fallback,
            'image_url' => $imgDir . $it->image,
            'image_fallback_url' => $it->image_fallback ? $imgDir . $it->image_fallback : null,
            'is_lifeform' => (bool) $it->is_lifeform,
            'booster_type' => $it->booster_type,
            'tier_key' => $it->tier_key,
            'categories' => $it->categories->pluck('key')->values()->all(),
        ];
    }

    /**
     * Deduct dark matter and grant the purchased item into the user's inventory.
     * Runs in a transaction with row-level lock on the user row.
     * Persists an audit row in shop_purchases for forensics.
     */
    public function purchase(User $user, ShopItem $item, ?string $ipAddress = null): UserItem
    {
        return DB::transaction(function () use ($user, $item, $ipAddress) {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->dark_matter < (int) $item->price_dm) {
                throw new RuntimeException('insufficient_dm');
            }

            $locked->dark_matter = (int) $locked->dark_matter - (int) $item->price_dm;
            $locked->save();

            // tier is VARCHAR(16) — too small for the sha1 ref (40 chars).
            // Identification of the purchased shop item is via source='shop' + source_ref=$item->id.
            // tier_key from shop_items (bronze/silver/gold/platinum) is used when present.
            $userItem = UserItem::create([
                'user_id'         => $locked->id,
                'item_type'       => 'shop_item',
                'tier'            => $item->tier_key,
                'category'        => 'items',
                'activation_type' => 'instant',
                'payload'         => [
                    'shop_ref' => $item->ref,
                    'name'     => $item->name,
                ],
                'status'          => 'available',
                'acquired_at'     => now(),
                'source'          => 'shop',
                'source_ref'      => (int) $item->id,
            ]);

            \OGame\Models\ShopPurchase::create([
                'user_id'      => $locked->id,
                'shop_item_id' => (int) $item->id,
                'user_item_id' => (int) $userItem->id,
                'dm_spent'     => (int) $item->price_dm,
                'item_name'    => mb_substr($item->name, 0, 100),
                'ip_address'   => $ipAddress,
                'created_at'   => now(),
            ]);

            return $userItem;
        });
    }
}

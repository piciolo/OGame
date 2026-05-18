<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OGame\Models\ShopCategory;
use OGame\Models\ShopItem;

/**
 * Seeds the 20 "Offerte speciali" items captured from OGame live.
 * Source: _research/shop/offerte_speciali/items.json
 *
 * Safe to run multiple times: upserts by ref and re-syncs pivot for category "offerte_speciali".
 */
class OfferteSpecialiSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('_research/shop/offerte_speciali/items.json');
        if (!file_exists($path)) {
            $this->command->error("Missing: $path");
            return;
        }
        $raw = json_decode((string) file_get_contents($path), true);
        $extMap = $raw['extended_descriptions'] ?? [];
        $items = $raw['items'] ?? [];

        DB::transaction(function () use ($items, $extMap) {
            $cat = ShopCategory::firstOrCreate(
                ['key' => 'offerte_speciali'],
                ['name' => 'Offerte speciali', 'sort_order' => 0]
            );

            $sort = 0;
            $keptIds = [];
            foreach ($items as $it) {
                $extKey = $it['extended_description_key'] ?? null;
                $ext = $extKey && isset($extMap[$extKey]) ? $extMap[$extKey] : null;

                $item = ShopItem::updateOrCreate(
                    ['ref' => $it['ref']],
                    [
                        'name' => $it['name'],
                        'description' => $it['effect_description'],
                        'extended_description' => $ext,
                        'effect_description' => $it['effect_description'],
                        'rules_description' => $it['rules_description'] ?? null,
                        'price_dm' => (int) $it['price_dm'],
                        'price_dm_original' => (int) $it['price_dm_original'],
                        'price_label' => $it['price_label'],
                        'price_label_original' => $it['price_label_original'],
                        'duration_seconds' => 0,
                        'duration_label' => $it['duration_label'],
                        'rarity' => $it['rarity'],
                        'image' => $it['image_primary'] . '.png',
                        'image_fallback' => $it['image_fallback'] . '.png',
                        'is_lifeform' => (bool) ($it['lifeform'] ?? false),
                        'booster_type' => $it['booster'],
                        'tier_key' => $it['tier'],
                        'sort_order' => $sort++,
                    ]
                );
                $keptIds[] = $item->id;

                DB::table('shop_item_category')->updateOrInsert(
                    ['shop_item_id' => $item->id, 'shop_category_id' => $cat->id],
                    ['shop_item_id' => $item->id, 'shop_category_id' => $cat->id]
                );
            }

            $this->command->info('Offerte Speciali: seeded ' . count($keptIds) . ' items.');
        });
    }
}

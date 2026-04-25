<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OGame\Models\ShopCategory;
use OGame\Models\ShopItem;

class ShopCatalogSeeder extends Seeder
{
    private const CATEGORY_LABELS = [
        'offerte_speciali'  => 'Offerte speciali',
        'seleziona_classe'  => 'Seleziona classe',
        'costruzione'       => 'Costruzione',
        'risorse'           => 'Risorse',
        'booster_30'        => 'Booster 30 giorni',
        'booster_90'        => 'Booster 90 giorni',
        'profilo'           => 'Profilo',
    ];

    public function run(): void
    {
        $path = database_path('seeders/data/shop_items.json');
        $raw = json_decode(file_get_contents($path), true);
        if (!is_array($raw) || !isset($raw['items'])) {
            $this->command->error('Invalid shop_items.json');
            return;
        }

        DB::transaction(function () use ($raw) {
            // Wipe pivot + items + categories (keep IDs free of orphans)
            DB::table('shop_item_category')->delete();
            DB::table('shop_items')->delete();
            DB::table('shop_categories')->delete();

            // Categories
            $catMap = [];
            $order = 0;
            foreach (self::CATEGORY_LABELS as $key => $label) {
                $cat = ShopCategory::create([
                    'key' => $key,
                    'name' => $label,
                    'sort_order' => $order++,
                ]);
                $catMap[$key] = $cat->id;
            }

            // Items
            $sort = 0;
            foreach ($raw['items'] as $it) {
                $item = ShopItem::create([
                    'ref' => $it['r'],
                    'name' => $it['n'],
                    'description' => $it['d'] ?? null,
                    'price_dm' => $this->parsePriceDm($it['p']),
                    'price_label' => $it['ps'],
                    'duration_seconds' => $this->parseDurationSeconds($it['t']),
                    'duration_label' => $it['t'],
                    'rarity' => $it['rar'],
                    'image' => $it['img'],
                    'sort_order' => $sort++,
                ]);

                $pivot = [];
                foreach ($it['c'] as $catKey) {
                    if (!isset($catMap[$catKey])) {
                        continue;
                    }
                    $pivot[] = [
                        'shop_item_id' => $item->id,
                        'shop_category_id' => $catMap[$catKey],
                    ];
                }
                if ($pivot !== []) {
                    DB::table('shop_item_category')->insert($pivot);
                }
            }

            $this->command->info(sprintf(
                'Shop catalog seeded: %d items, %d categories',
                count($raw['items']),
                count($catMap)
            ));
        });
    }

    /**
     * "360.000 Materia Oscura" -> 360000
     * "700 Materia Oscura"      -> 700
     */
    private function parsePriceDm(string $label): int
    {
        $digits = preg_replace('/[^0-9]/', '', explode(' ', trim($label))[0] ?? '');
        return (int) $digits;
    }

    /**
     * Parse OGame Italian duration strings:
     *   s = settimana (604800), g = giorno (86400), o = ora (3600), m = minuto (60)
     *   "Permanente..." -> null
     *   "ora" (standalone) -> 3600
     */
    private function parseDurationSeconds(string $label): ?int
    {
        $label = trim($label);
        if ($label === '') {
            return null;
        }
        if (stripos($label, 'Permanente') === 0) {
            return null;
        }
        if (strcasecmp($label, 'ora') === 0) {
            return 3600;
        }

        $units = ['s' => 604800, 'g' => 86400, 'o' => 3600, 'm' => 60];
        $total = 0;
        if (preg_match_all('/(\d+)\s*([sgom])\b/i', $label, $m, PREG_SET_ORDER)) {
            foreach ($m as $match) {
                $total += ((int) $match[1]) * $units[strtolower($match[2])];
            }
        }
        return $total > 0 ? $total : null;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\ShopItem;

/**
 * Applies extended_description (full OGame native detail panel text) and
 * duration_label scraped from live OGame to shop_items rows, matched by name.
 *
 * Source data: _research/shop/extended_descriptions.json
 * Format: { "Item Name": { "description": "...", "duration": "Durata: ora" } }
 *
 * Idempotent: re-running the seeder is safe.
 */
class ShopExtendedDescriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/extended_descriptions.json');
        if (!file_exists($path)) {
            $this->command->error("Missing: $path");
            return;
        }

        $map = json_decode((string) file_get_contents($path), true);
        if (!is_array($map)) {
            $this->command->error('Invalid JSON');
            return;
        }

        $updated = 0;
        $missing = [];
        $durSkipped = 0;
        foreach ($map as $name => $data) {
            $desc = trim((string) ($data['description'] ?? ''));
            $dur = trim((string) ($data['duration'] ?? ''));
            $dur = preg_replace('/^Durata:\s*/i', '', $dur);
            $dur = trim((string) explode("\n", $dur)[0]);

            $update = ['extended_description' => $desc];
            if ($dur !== '' && mb_strlen($dur) <= 60) {
                $update['duration_label'] = $dur;
            } elseif ($dur !== '') {
                $durSkipped++;
            }

            $n = ShopItem::query()->where('name', $name)->update($update);
            if ($n === 0) {
                $missing[] = $name;
            }
            $updated += $n;
        }

        $this->command->info("Extended descriptions seeded: $updated rows, $durSkipped duration skipped, " . count($missing) . ' names without match.');
        foreach ($missing as $m) {
            $this->command->warn("  no match: $m");
        }
    }
}

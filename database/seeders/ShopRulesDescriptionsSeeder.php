<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Applies rules_description to shop_items rows, matched by ref.
 *
 * Source: database/seeders/data/rules_descriptions.json
 * Format: { "<ref>": "<html rules text>" }
 *
 * Idempotent: safe to re-run.
 */
class ShopRulesDescriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/rules_descriptions.json');
        if (!file_exists($path)) {
            $this->command->error("Missing: $path");
            return;
        }

        $map = json_decode((string) file_get_contents($path), true);
        if (!is_array($map)) {
            $this->command->error('Invalid JSON in rules_descriptions.json');
            return;
        }

        $updated = 0;
        $missing = [];

        foreach ($map as $ref => $rules) {
            $n = DB::table('shop_items')
                ->where('ref', $ref)
                ->update(['rules_description' => $rules]);

            if ($n === 0) {
                $missing[] = $ref;
            }
            $updated += $n;
        }

        $this->command->info("Rules descriptions seeded: {$updated} rows updated, " . count($missing) . ' refs without match.');
        foreach ($missing as $ref) {
            $this->command->warn("  no match for ref: {$ref}");
        }
    }
}

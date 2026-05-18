<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\Achievement;
use OGame\Models\AchievementTier;

/**
 * Seed dei 51 achievement OGame.
 *
 * Sorgente unica: `database/seeders/data/achievements.json`
 *
 * Il file è generato da `_research/achievements_authoritative.tsv`, che a sua
 * volta è il dump 1:1 dello scraping live di
 * https://s275-it.ogame.gameforge.com/game/index.php?page=ingame&component=playerprofile
 * (tab Trofei → Riepilogo, sezione achievement).
 *
 * Schema per achievement:
 *   {
 *     "display_number": int,
 *     "machine_name":   string (chiave usata dal progress tracker),
 *     "name_it":        string,
 *     "desc_it":        string (= description del tier 1),
 *     "tiers": [{
 *       "tier":                int (1..5),
 *       "target":              int (0 = event-based, sbloccato solo via API dedicata),
 *       "description":         string (testo per-tier dal live),
 *       "reward_type":         "avatar"|"skin"|"title"|null (null = nessuna ricompensa),
 *       "reward_machine_name": string|null,
 *       "title_text":          string|null (testo titolo, solo per reward_type=title)
 *     }, ...]
 *   }
 */
class AchievementsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/achievements.json');
        if (!is_file($path)) {
            $this->command->error('Missing achievements.json');
            return;
        }

        $rows = json_decode((string) file_get_contents($path), true) ?: [];

        foreach ($rows as $row) {
            $achievement = Achievement::updateOrCreate(
                ['machine_name' => $row['machine_name']],
                [
                    'display_number' => (int) $row['display_number'],
                    'name_key' => 't_ingame.achievements.name_'.$row['machine_name'],
                    'description_key' => 't_ingame.achievements.desc_'.$row['machine_name'],
                    'category' => 'base',
                    'is_secret' => false,
                    'sort_order' => (int) $row['display_number'],
                ]
            );

            // Sincronizza i tier (delete+recreate per semplicità)
            AchievementTier::where('achievement_id', $achievement->id)->delete();
            foreach ($row['tiers'] ?? [] as $t) {
                AchievementTier::create([
                    'achievement_id' => $achievement->id,
                    'tier' => (int) $t['tier'],
                    'target' => (int) ($t['target'] ?? 0),
                    'description_text' => $t['description'] ?? null,
                    'reward_type' => $t['reward_type'] ?? null,
                    'reward_machine_name' => $t['reward_machine_name'] ?? null,
                    'title_text' => $t['title_text'] ?? null,
                ]);
            }
        }

        $this->command->info('Seeded '.count($rows).' achievements (sorgente: scraping live OGame).');
    }
}

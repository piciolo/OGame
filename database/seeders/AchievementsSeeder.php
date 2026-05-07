<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\Achievement;
use OGame\Models\AchievementTier;

/**
 * Seed dei 51 achievement OGame.
 * Sorgente: scraping diretto da https://s275-it.ogame.gameforge.com/game/index.php?page=ingame&component=playerprofile
 * (tab Trofei → Riepilogo, sezione "achievementContentList_unlocks").
 *
 * Dati: database/seeders/data/achievements.json — contiene per ogni achievement:
 *   - display_number (es. 1..51)
 *   - machine_name (slug)
 *   - name_it (titolo italiano)
 *   - desc_it (descrizione italiana base)
 *   - tiers[] (5 livelli, ognuno con target + reward_type + reward_machine_name)
 *
 * Le 2 voci stagionali segrete (4000100_X, 8000100_X) NON sono qui — andranno
 * gestite separatamente quando il sistema stagionale sarà implementato.
 */
class AchievementsSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/achievements.json');
        if (!is_file($path)) {
            $this->command?->error('Missing data file: '.$path);
            return;
        }

        $rows = json_decode((string) file_get_contents($path), true);
        if (!is_array($rows)) {
            $this->command?->error('Invalid JSON in '.$path);
            return;
        }

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
                    'target' => (int) $t['target'],
                    'reward_type' => (string) $t['reward_type'],
                    'reward_machine_name' => (string) $t['reward_machine_name'],
                ]);
            }
        }

        $this->command?->info('Seeded '.count($rows).' achievements.');
    }
}

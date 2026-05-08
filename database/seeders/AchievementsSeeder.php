<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\Achievement;
use OGame\Models\AchievementTier;

/**
 * Seed dei 51 achievement OGame.
 *
 * Sorgente:
 *  - `achievements.json`            → mapping display_number → machine_name/name_key/desc_key
 *  - `achievements_extracted.json`  → dati REALI scrappati da OGame.it
 *                                     (target, descrizione per-tier, reward_type, reward_machine_name, title_text)
 *
 * I due file sono allineati per `display_number` (= ordine OGame `#1`..`#51`).
 *
 * Le 92 ricompense `null` nell'extracted corrispondono a tier "segreti" che
 * OGame nasconde finché non vengono sbloccati: in quel caso conserviamo i
 * machine_name esistenti del file `achievements.json` (best-effort).
 */
class AchievementsSeeder extends Seeder
{
    public function run(): void
    {
        $base = database_path('seeders/data/achievements.json');
        $real = database_path('seeders/data/achievements_extracted.json');

        if (!is_file($base) || !is_file($real)) {
            $this->command?->error('Missing data files (achievements.json / achievements_extracted.json)');
            return;
        }

        $rows = json_decode((string) file_get_contents($base), true) ?: [];
        $extracted = json_decode((string) file_get_contents($real), true) ?: [];

        // Override per i title_text completi (sorgente: titles_extracted.json,
        // generato dall'HAR autoritativo del componente trofei OGame). Mappa
        // machine_name → testo italiano. Sovrascrive valori null/parziali.
        $titlesOverridePath = database_path('seeders/data/titles_extracted.json');
        $titlesOverride = is_file($titlesOverridePath)
            ? (json_decode((string) file_get_contents($titlesOverridePath), true) ?: [])
            : [];

        // Allineiamo per display_number = indice 1-based.
        $extractedByIndex = [];
        foreach ($extracted as $i => $e) {
            $extractedByIndex[$i + 1] = $e;
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

            $extractedRow = $extractedByIndex[(int) $row['display_number']] ?? null;
            $extractedTiers = [];
            if ($extractedRow !== null) {
                foreach ($extractedRow['tiers'] ?? [] as $t) {
                    $extractedTiers[(int) $t['tier']] = $t;
                }
            }

            // Sincronizza i tier (delete+recreate per semplicità)
            AchievementTier::where('achievement_id', $achievement->id)->delete();
            foreach ($row['tiers'] ?? [] as $t) {
                $tierNum = (int) $t['tier'];
                $ex = $extractedTiers[$tierNum] ?? [];

                // Reward: prendi sempre prima quello reale scrappato; fallback al base file
                // se OGame nascondeva la reward (achievement segreto).
                $rewardType = $ex['reward_type'] ?? $t['reward_type'];
                $rewardMachine = $ex['reward_machine_name'] ?? $t['reward_machine_name'];
                $titleText = $ex['reward_title_text'] ?? null;
                // Override autoritativo per titoli mancanti (es. tier "segreti"
                // nello scrape iniziale, ora recuperati dal HAR completo).
                if ($rewardType === 'title' && empty($titleText) && isset($titlesOverride[$rewardMachine])) {
                    $titleText = $titlesOverride[$rewardMachine];
                }
                $description = $ex['description'] ?? null;
                $target = (int) ($ex['target'] ?? $t['target']);

                AchievementTier::create([
                    'achievement_id' => $achievement->id,
                    'tier' => $tierNum,
                    'target' => $target,
                    'reward_type' => (string) $rewardType,
                    'reward_machine_name' => (string) $rewardMachine,
                    'description_text' => $description,
                    'title_text' => $titleText,
                ]);
            }
        }

        $this->command?->info('Seeded '.count($rows).' achievements (con dati reali OGame).');
    }
}

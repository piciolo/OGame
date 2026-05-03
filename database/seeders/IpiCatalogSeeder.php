<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OGame\Models\IpiChapter;
use OGame\Models\IpiChapterReward;
use OGame\Models\IpiTask;
use OGame\Models\IpiTaskAction;
use OGame\Models\IpiTaskReward;

/**
 * Seeds the IPI (Initial Player Instructions / Panoramica Direttive) static catalog
 * from the reverse-engineered dump at database/seeders/data/ipi.json.
 *
 * Source extraction: _research/extracted/ipioverview/2026-05-03_pilot/data-full.json
 */
class IpiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/ipi.json');
        if (!file_exists($path)) {
            $this->command->error("IPI seed file missing: {$path}");
            return;
        }
        $raw = json_decode(file_get_contents($path), true);
        if (!is_array($raw) || !isset($raw['chapters'])) {
            $this->command->error('Invalid ipi.json structure');
            return;
        }

        DB::transaction(function () use ($raw) {
            // Wipe in dependency order
            DB::table('ipi_task_rewards')->delete();
            DB::table('ipi_task_actions')->delete();
            DB::table('ipi_chapter_rewards')->delete();
            DB::table('ipi_tasks')->delete();
            DB::table('ipi_chapters')->delete();

            // Cap VI (4006) — lifeform-only — is hidden until the lifeform feature is implemented.
            // Cap VII (4007) shifts up to display as "VI" in the UI; its title is rewritten accordingly.
            $chapterOverrides = [
                4006 => ['sort_order' => 6, 'enabled' => false /* keeps original VI title */],
                4007 => ['sort_order' => 5, 'enabled' => true,  'title' => 'Direttiva VI – Ulteriori suggerimenti'],
            ];

            $chapterOrder = 0;
            foreach ($raw['chapters'] as $chapterId => $cdata) {
                $cid = (int)$chapterId;
                $override = $chapterOverrides[$cid] ?? null;
                IpiChapter::create([
                    'id' => $cid,
                    'sort_order' => $override['sort_order'] ?? $chapterOrder++,
                    'title' => $override['title'] ?? $cdata['chapterTitle'],
                    'enabled' => $override['enabled'] ?? true,
                ]);

                foreach ($cdata['chapterRewards'] as $cr) {
                    IpiChapterReward::create([
                        'chapter_id' => (int)$chapterId,
                        'resource_index' => $this->resourceIndex($cr['resource']),
                        'quantity' => $cr['qty'],
                    ]);
                }

                $taskOrder = 0;
                foreach ($cdata['tasks'] as $t) {
                    IpiTask::create([
                        'id' => $t['id'],
                        'chapter_id' => (int)$chapterId,
                        'sort_order' => $taskOrder++,
                        'title' => $t['title'],
                        'description' => $t['description'],
                        'image' => $t['image'],
                        'total_steps' => $t['total'],
                    ]);

                    $actionOrder = 0;
                    foreach ($t['actions'] as $a) {
                        IpiTaskAction::create([
                            'task_id' => $t['id'],
                            'sort_order' => $actionOrder++,
                            'text' => $a['text'],
                            // requirement_type/target/value/planet_scope/hint_target left NULL for now;
                            // mapping happens in M3 when we wire up progress tracking.
                        ]);
                    }

                    foreach ($t['rewards'] as $r) {
                        IpiTaskReward::create([
                            'task_id' => $t['id'],
                            'resource_index' => $this->resourceIndex($r['resource']),
                            'quantity' => $r['qty'],
                        ]);
                    }
                }
            }
        });

        $this->command->info(sprintf(
            'IPI seeded: %d chapters, %d tasks, %d actions, %d task rewards, %d chapter rewards',
            IpiChapter::count(),
            IpiTask::count(),
            IpiTaskAction::count(),
            IpiTaskReward::count(),
            IpiChapterReward::count(),
        ));
    }

    /**
     * Map OGame's "resource-N" CSS class to a numeric index.
     */
    private function resourceIndex(string $resource): int
    {
        return (int)str_replace('resource-', '', $resource);
    }
}

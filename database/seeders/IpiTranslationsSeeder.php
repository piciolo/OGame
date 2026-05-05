<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use OGame\Models\IpiChapter;
use OGame\Models\IpiTask;
use OGame\Models\IpiTaskAction;

/**
 * Populates *_translations JSON columns on IPI catalog tables with content scraped
 * from OGame's official servers (27 native languages). Adds aliases for the 12
 * regional variants used by OGameX (cs->cz, da->dk, ja->jp, etc.) so all 39 lang
 * codes resolve to a localized string.
 *
 * Source: database/seeders/data/ipi_content_translations.json
 *   Shape: {<ogame_lang>: {<chapterId>: {chapterTitle, tasks: [{id, title, description, actions: [{sortOrder, text}]}]}}}
 *
 * Run: php artisan db:seed --class=IpiTranslationsSeeder
 */
class IpiTranslationsSeeder extends Seeder
{
    /** OGameX directory -> OGame language code (source for translations). */
    private const ALIASES = [
        'cs'    => 'cz',
        'da'    => 'dk',
        'el'    => 'gr',
        'en_US' => 'us',
        'es_AR' => 'ar',
        'es_MX' => 'mx',
        'ja'    => 'jp',
        'pt_BR' => 'br',
        'sl'    => 'si',
        'sr'    => 'yu',
        'sv'    => 'se',
        'zh_TW' => 'tw',
    ];

    public function run(): void
    {
        $path = database_path('seeders/data/ipi_content_translations.json');
        if (!file_exists($path)) {
            $this->command->error("Missing {$path}");
            return;
        }
        $raw = json_decode(file_get_contents($path), true);
        if (!is_array($raw)) {
            $this->command->error('Invalid JSON');
            return;
        }

        // Expand aliases (OGameX-specific directory codes that mirror an OGame native one).
        foreach (self::ALIASES as $aliasCode => $sourceCode) {
            if (!isset($raw[$aliasCode]) && isset($raw[$sourceCode])) {
                $raw[$aliasCode] = $raw[$sourceCode];
            }
        }
        $allLocales = array_keys($raw);

        // Build inverted maps:
        //   chapterTitleByLocale[chapterId][locale]  = string
        //   taskTitleByLocale[taskId][locale]        = string
        //   taskDescByLocale[taskId][locale]         = string
        //   actionTextBy[taskId][sortOrder][locale]  = string
        $chapterTitleByLocale = [];
        $taskTitleByLocale = [];
        $taskDescByLocale = [];
        $actionTextByLocale = [];

        foreach ($raw as $locale => $chapters) {
            if (!is_array($chapters)) continue;
            foreach ($chapters as $cid => $cdata) {
                $cid = (int)$cid;
                if (!empty($cdata['chapterTitle'])) {
                    $chapterTitleByLocale[$cid][$locale] = $cdata['chapterTitle'];
                }
                foreach (($cdata['tasks'] ?? []) as $t) {
                    $tid = (int)($t['id'] ?? 0);
                    if (!$tid) continue;
                    if (!empty($t['title']))       $taskTitleByLocale[$tid][$locale] = $t['title'];
                    if (!empty($t['description'])) $taskDescByLocale[$tid][$locale]  = $t['description'];
                    foreach (($t['actions'] ?? []) as $a) {
                        $so = (int)($a['sortOrder'] ?? -1);
                        if ($so < 0) continue;
                        if (!empty($a['text'])) $actionTextByLocale[$tid][$so][$locale] = $a['text'];
                    }
                }
            }
        }

        $stats = ['chapters' => 0, 'tasks' => 0, 'actions' => 0, 'actions_missing' => 0];

        DB::transaction(function () use (&$stats, $chapterTitleByLocale, $taskTitleByLocale, $taskDescByLocale, $actionTextByLocale) {
            // Chapters
            foreach (IpiChapter::all() as $chapter) {
                $tr = $chapterTitleByLocale[$chapter->id] ?? null;
                if ($tr) {
                    $chapter->title_translations = $tr;
                    $chapter->save();
                    $stats['chapters']++;
                }
            }

            // Tasks
            foreach (IpiTask::all() as $task) {
                $titleTr = $taskTitleByLocale[$task->id] ?? null;
                $descTr  = $taskDescByLocale[$task->id]  ?? null;
                $dirty = false;
                if ($titleTr) { $task->title_translations = $titleTr; $dirty = true; }
                if ($descTr)  { $task->description_translations = $descTr; $dirty = true; }
                if ($dirty) {
                    $task->save();
                    $stats['tasks']++;
                }
            }

            // Actions: lookup by (task_id, sort_order)
            foreach (IpiTaskAction::all() as $action) {
                $tr = $actionTextByLocale[$action->task_id][$action->sort_order] ?? null;
                if ($tr) {
                    $action->text_translations = $tr;
                    $action->save();
                    $stats['actions']++;
                } else {
                    $stats['actions_missing']++;
                }
            }
        });

        $this->command->info(sprintf(
            'IPI translations populated: %d chapters, %d tasks, %d actions (missing %d, locales=%d)',
            $stats['chapters'], $stats['tasks'], $stats['actions'], $stats['actions_missing'], count($allLocales)
        ));
    }
}

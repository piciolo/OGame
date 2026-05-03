<?php

namespace OGame\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OGame\Models\IpiChapter;
use OGame\Models\IpiPlayerChapterCollected;
use OGame\Models\IpiPlayerProgress;
use OGame\Models\IpiTask;
use OGame\Models\IpiTaskAction;
use OGame\Models\Planet;
use OGame\Models\User;

/**
 * Service backing the "Panoramica direttive" overlay (M3 — full read+write).
 *
 * Conventions:
 *   - The welcome task (id 5001) is auto-collected the first time a player opens the overlay.
 *   - progress_count on ipi_player_progress is interpreted as the *number of completed
 *     actions* in sort_order. So action #N is considered complete when progress_count > N.
 *     This is sequential, matching the OGame UX.
 *   - When a player tracks a task, every other tracked task for that user is reset to 'none'.
 *   - State transitions: none → tracked → completed → collected (see WELCOME_TASK_ID for the
 *     auto-collected exception).
 *   - Reward distribution ignores planet storage cap (replicating OGame's behaviour).
 */
class IpiOverviewService
{
    public const WELCOME_TASK_ID = 5001;
    public const DEFAULT_CHAPTER_ID = 4001;

    public const RESOURCE_METAL = 0;
    public const RESOURCE_CRYSTAL = 1;
    public const RESOURCE_DEUTERIUM = 2;
    public const RESOURCE_ENERGY = 3;
    public const RESOURCE_DARK_MATTER = 4;

    /**
     * Full chapter with tasks + actions + rewards eager-loaded.
     * Returns null for disabled chapters (e.g. Cap VI lifeform until that feature is implemented).
     */
    public function getChapter(int $chapterId): ?IpiChapter
    {
        $chapter = IpiChapter::with([
            'rewards',
            'tasks.actions',
            'tasks.rewards',
        ])->find($chapterId);
        if (!$chapter || !$chapter->enabled) return null;
        return $chapter;
    }

    public function getAllChapters(): EloquentCollection
    {
        return IpiChapter::where('enabled', true)->orderBy('sort_order')->get();
    }

    /**
     * Returns a map task_id => IpiPlayerProgress for all rows belonging to $userId.
     * Pass this to getTaskState/getTaskProgress to avoid N+1 in the Blade.
     */
    public function getProgressMap(?int $userId): EloquentCollection
    {
        if ($userId === null) return new EloquentCollection();
        return IpiPlayerProgress::where('user_id', $userId)->get()->keyBy('task_id');
    }

    /**
     * Per-task state, replacing OGame's data-state attribute on .ipiTaskItem.
     */
    public function getTaskState(IpiTask $task, ?int $userId, ?EloquentCollection $progressMap = null): string
    {
        if ($task->id === self::WELCOME_TASK_ID) {
            // Auto-collect on first overlay open if no row yet.
            $progressMap ??= $this->getProgressMap($userId);
            $existing = $progressMap->get(self::WELCOME_TASK_ID);
            if (!$existing && $userId !== null) {
                $this->autoCollectWelcomeTask($userId, $task);
            }
            return IpiPlayerProgress::STATE_COLLECTED;
        }

        $progressMap ??= $this->getProgressMap($userId);
        $row = $progressMap->get($task->id);
        return $row?->state ?? IpiPlayerProgress::STATE_NONE;
    }

    public function getTaskProgress(IpiTask $task, ?int $userId, ?EloquentCollection $progressMap = null): int
    {
        if ($task->id === self::WELCOME_TASK_ID) {
            return $task->total_steps;
        }
        $progressMap ??= $this->getProgressMap($userId);
        return (int)($progressMap->get($task->id)?->progress_count ?? 0);
    }

    /**
     * Whether the player can collect the chapter completion bonus right now.
     * True iff every non-welcome task of the chapter is at least 'completed'
     * AND the chapter itself is not yet in ipi_player_chapter_collected.
     */
    public function isChapterCollectable(IpiChapter $chapter, ?int $userId, ?EloquentCollection $progressMap = null): bool
    {
        if ($userId === null) return false;
        if (!$chapter->enabled) return false;
        if (IpiPlayerChapterCollected::where('user_id', $userId)->where('chapter_id', $chapter->id)->exists()) {
            return false;
        }
        $progressMap ??= $this->getProgressMap($userId);
        foreach ($chapter->tasks as $task) {
            if ($task->id === self::WELCOME_TASK_ID) continue;
            $state = $progressMap->get($task->id)?->state;
            if (!in_array($state, [IpiPlayerProgress::STATE_COMPLETED, IpiPlayerProgress::STATE_COLLECTED], true)) {
                return false;
            }
        }
        return true;
    }

    public function isActionCompleted(IpiTaskAction $action, ?int $userId, ?EloquentCollection $progressMap = null): bool
    {
        if ($action->task_id === self::WELCOME_TASK_ID) {
            return true;
        }
        $progressMap ??= $this->getProgressMap($userId);
        $progress = (int)($progressMap->get($action->task_id)?->progress_count ?? 0);
        return $progress > $action->sort_order;
    }

    /**
     * Returns the next "to do" action for the side-panel widget.
     *
     * Algorithm:
     *   1. If the user has a tracked task, return the first incomplete action of it.
     *   2. Else, walk chapters in order, then tasks in order, and return the first action
     *      of the first non-collected, non-welcome task whose chapter is also not fully collected.
     *   3. If nothing is left to do, return null (widget hides).
     *
     * @return array{title: string, highlights: string[]}|null
     */
    public function getCurrentNextAction(?int $userId): ?array
    {
        if ($userId === null) return null;

        // Refresh progress before computing the next action. Cached per-request to avoid
        // re-running the recompute when both the side-panel composer and the overlay
        // controller call this in the same render.
        static $recomputedFor = [];
        if (empty($recomputedFor[$userId])) {
            app(IpiProgressService::class)->recompute($userId);
            $recomputedFor[$userId] = true;
        }

        $progressMap = $this->getProgressMap($userId);

        // 1. Tracked task
        $tracked = $progressMap->firstWhere('state', IpiPlayerProgress::STATE_TRACKED);
        if ($tracked) {
            $action = IpiTaskAction::where('task_id', $tracked->task_id)
                ->orderBy('sort_order')
                ->skip((int)$tracked->progress_count)
                ->first();
            if ($action) {
                return $this->actionToWidgetPayload($action);
            }
        }

        // 2. First non-collected task in chapter order — skip disabled chapters
        $tasks = IpiTask::query()
            ->join('ipi_chapters', 'ipi_chapters.id', '=', 'ipi_tasks.chapter_id')
            ->where('ipi_chapters.enabled', true)
            ->orderBy('ipi_chapters.sort_order')
            ->orderBy('ipi_tasks.sort_order')
            ->select('ipi_tasks.*')
            ->get();

        foreach ($tasks as $task) {
            if ($task->id === self::WELCOME_TASK_ID) continue;
            $row = $progressMap->get($task->id);
            if ($row && $row->state === IpiPlayerProgress::STATE_COLLECTED) continue;
            $action = IpiTaskAction::where('task_id', $task->id)
                ->orderBy('sort_order')
                ->skip((int)($row?->progress_count ?? 0))
                ->first();
            if ($action) {
                return $this->actionToWidgetPayload($action);
            }
        }

        return null;
    }

    private function actionToWidgetPayload(IpiTaskAction $action): array
    {
        return [
            'title' => $this->localized($action, 'text'),
            'highlights' => $action->hint_target ? [$action->hint_target] : [],
        ];
    }

    /**
     * Resolve a task image basename ("task5001.jpg") to a public URL.
     * Files live in public/img/ipi/tasks/ (extracted from OGame's CDN).
     */
    public function imageUrl(string $basename): string
    {
        return asset('img/ipi/tasks/' . $basename);
    }

    /**
     * URL of the small green checkmark used for completed actions/tasks.
     */
    public function checkmarkUrl(): string
    {
        return asset('img/ipi/checkmark.gif');
    }

    /**
     * Returns the localised value of a translatable IPI text field. Looks up the
     * current app locale in the model's *_translations JSON column and falls back
     * to the bare column (Italian default) if the translation is missing.
     *
     * Example: localized($task, 'title') reads $task->title_translations[$locale]
     * with fallback to $task->title.
     */
    public function localized($model, string $field): string
    {
        $locale = app()->getLocale();
        $translations = $model->{$field . '_translations'} ?? null;
        if (is_array($translations) && !empty($translations[$locale])) {
            return (string)$translations[$locale];
        }
        return (string)($model->{$field} ?? '');
    }

    /**
     * Localised label for a resource index (matches OGame's tooltip format
     * "Metallo: 5000" rather than the bare value).
     */
    public function resourceLabel(int $idx): string
    {
        return match ($idx) {
            self::RESOURCE_METAL => __('t_ingame.resource_settings.metal'),
            self::RESOURCE_CRYSTAL => __('t_ingame.resource_settings.crystal'),
            self::RESOURCE_DEUTERIUM => __('t_ingame.resource_settings.deuterium'),
            self::RESOURCE_ENERGY => __('t_ingame.resource_settings.energy'),
            self::RESOURCE_DARK_MATTER => __('t_ingame.shared.dark_matter'),
            default => 'Resource ' . $idx,
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MUTATIONS — track / collect
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Toggle the "tracked" state for a task. Side effects:
     *   - If the task is already tracked, untrack it (state → none).
     *   - Otherwise, untrack any other tracked task and mark this one tracked.
     *
     * @return array Response payload matching OGame's trackTask JSON shape.
     */
    public function trackTask(int $userId, int $taskId): array
    {
        if ($err = $this->guardUserCanMutate($userId)) return $err;
        $task = IpiTask::find($taskId);
        if (!$task || $task->id === self::WELCOME_TASK_ID) {
            return ['success' => false, 'error' => 'Invalid task'];
        }

        DB::transaction(function () use ($userId, $taskId) {
            // Untrack any other tracked tasks for this user
            IpiPlayerProgress::where('user_id', $userId)
                ->where('task_id', '!=', $taskId)
                ->where('state', IpiPlayerProgress::STATE_TRACKED)
                ->update([
                    'state' => IpiPlayerProgress::STATE_NONE,
                    'tracked_at' => null,
                ]);

            // Toggle this task
            $row = IpiPlayerProgress::firstOrCreate(
                ['user_id' => $userId, 'task_id' => $taskId],
                ['state' => IpiPlayerProgress::STATE_NONE, 'progress_count' => 0]
            );

            if ($row->state === IpiPlayerProgress::STATE_TRACKED) {
                $row->update(['state' => IpiPlayerProgress::STATE_NONE, 'tracked_at' => null]);
            } elseif (in_array($row->state, [IpiPlayerProgress::STATE_NONE, IpiPlayerProgress::STATE_COMPLETED], true)) {
                $row->update(['state' => IpiPlayerProgress::STATE_TRACKED, 'tracked_at' => Carbon::now()]);
            }
        });

        return [
            'success' => true,
            'newAjaxToken' => csrf_token(),
            'trackedAction' => $this->getCurrentNextAction($userId),
        ];
    }

    /**
     * Award a single task's rewards to the player and mark it collected.
     * Returns OGame-shaped response.
     */
    public function collectTask(int $userId, int $taskId): array
    {
        if ($err = $this->guardUserCanMutate($userId)) return $err;
        $task = IpiTask::with('rewards')->find($taskId);
        if (!$task) return ['success' => false, 'error' => 'Invalid task'];

        $rendered = '';
        $alreadyCollected = false;
        DB::transaction(function () use ($userId, $task, &$rendered, &$alreadyCollected) {
            // Lock the row to serialise concurrent collects on the same task.
            $row = IpiPlayerProgress::where('user_id', $userId)
                ->where('task_id', $task->id)
                ->lockForUpdate()
                ->first();
            if (!$row || $row->state !== IpiPlayerProgress::STATE_COMPLETED) {
                $alreadyCollected = true;
                return;
            }
            $rendered = $this->awardRewardsToUser($userId, $task->rewards->all());
            $row->update([
                'state' => IpiPlayerProgress::STATE_COLLECTED,
                'collected_at' => Carbon::now(),
            ]);
        });
        if ($alreadyCollected) {
            return ['success' => false, 'error' => 'Task not completed'];
        }

        return [
            'success' => true,
            'newAjaxToken' => csrf_token(),
            'claimedRewardsRendered' => $rendered,
            'unclaimedRewards' => $this->countUnclaimedRewardsForChapter($userId, $task->chapter_id),
        ];
    }

    /**
     * Award the chapter completion bonus + auto-collect any leftover task rewards.
     */
    public function collectChapter(int $userId, int $chapterId): array
    {
        if ($err = $this->guardUserCanMutate($userId)) return $err;
        $chapter = IpiChapter::with(['rewards', 'tasks.rewards'])->find($chapterId);
        if (!$chapter) return ['success' => false, 'error' => 'Invalid chapter'];

        // Verify already collected
        if (IpiPlayerChapterCollected::where('user_id', $userId)->where('chapter_id', $chapterId)->exists()) {
            return ['success' => false, 'error' => 'Already collected'];
        }

        // Verify every non-welcome task of the chapter is at least completed
        $progressMap = $this->getProgressMap($userId);
        foreach ($chapter->tasks as $task) {
            if ($task->id === self::WELCOME_TASK_ID) continue;
            $state = $progressMap->get($task->id)?->state;
            if (!in_array($state, [IpiPlayerProgress::STATE_COMPLETED, IpiPlayerProgress::STATE_COLLECTED], true)) {
                return ['success' => false, 'error' => 'Chapter not fully completed'];
            }
        }

        $rendered = '';
        DB::transaction(function () use ($userId, $chapter, &$rendered) {
            // Auto-collect any task rewards still pending
            foreach ($chapter->tasks as $task) {
                if ($task->id === self::WELCOME_TASK_ID) continue;
                $row = IpiPlayerProgress::where('user_id', $userId)->where('task_id', $task->id)->first();
                if ($row && $row->state === IpiPlayerProgress::STATE_COMPLETED) {
                    $this->awardRewardsToUser($userId, $task->rewards->all());
                    $row->update(['state' => IpiPlayerProgress::STATE_COLLECTED, 'collected_at' => Carbon::now()]);
                }
            }
            // Award chapter bonus
            $rendered = $this->awardRewardsToUser($userId, $chapter->rewards->all());
            IpiPlayerChapterCollected::create([
                'user_id' => $userId,
                'chapter_id' => $chapter->id,
                'collected_at' => Carbon::now(),
            ]);
        });

        return [
            'success' => true,
            'newAjaxToken' => csrf_token(),
            'claimedRewardsRendered' => $rendered,
        ];
    }

    /**
     * Returns an error payload if the user cannot mutate IPI state right now
     * (vacation mode, banned, missing user). Returns null if all checks pass.
     */
    private function guardUserCanMutate(int $userId): ?array
    {
        $user = User::find($userId);
        if (!$user) {
            return ['success' => false, 'error' => 'User not found', 'newAjaxToken' => csrf_token()];
        }
        if ($user->isBanned()) {
            return ['success' => false, 'error' => 'Account banned', 'newAjaxToken' => csrf_token()];
        }
        if ($user->vacation_mode) {
            return ['success' => false, 'error' => 'Vacation mode active', 'newAjaxToken' => csrf_token()];
        }
        return null;
    }

    /**
     * Public entry-point for auto-collecting the welcome task. Idempotent: safe
     * to call repeatedly. Used by both getTaskState() (lazy on first overlay open)
     * and IpiProgressService::recompute() (eager on first authenticated request).
     */
    public function ensureWelcomeCollected(int $userId): void
    {
        $exists = IpiPlayerProgress::where('user_id', $userId)
            ->where('task_id', self::WELCOME_TASK_ID)
            ->exists();
        if ($exists) return;
        $task = IpiTask::with('rewards')->find(self::WELCOME_TASK_ID);
        if (!$task) return;
        $this->autoCollectWelcomeTask($userId, $task);
    }

    /**
     * Auto-collect the welcome task on first overlay open.
     */
    private function autoCollectWelcomeTask(int $userId, IpiTask $task): void
    {
        DB::transaction(function () use ($userId, $task) {
            IpiPlayerProgress::firstOrCreate(
                ['user_id' => $userId, 'task_id' => $task->id],
                [
                    'state' => IpiPlayerProgress::STATE_COLLECTED,
                    'progress_count' => $task->total_steps,
                    'collected_at' => Carbon::now(),
                ]
            );
            // Award welcome reward (500 dark matter)
            $this->awardRewardsToUser($userId, $task->rewards->all());
        });
    }

    /**
     * Apply reward array to user/planet. Storage cap is INTENTIONALLY ignored
     * (this matches OGame's behaviour for IPI rewards).
     *
     * @param array<\OGame\Models\IpiTaskReward|\OGame\Models\IpiChapterReward> $rewards
     * @return string  HTML-ish rendered summary for fadeBox display
     */
    private function awardRewardsToUser(int $userId, array $rewards): string
    {
        if (empty($rewards)) return '';
        $user = User::find($userId);
        if (!$user) return '';
        // Home planet = the first Planet (lowest id) of the user. We don't rely on
        // planet_current because freshly-registered players have it set to NULL.
        $planet = Planet::where('user_id', $userId)->where('planet_type', 1)->orderBy('id')->first();

        $bits = [];
        foreach ($rewards as $r) {
            $idx = (int)$r->resource_index;
            $qty = (int)$r->quantity;
            if ($qty <= 0) continue;

            switch ($idx) {
                case self::RESOURCE_METAL:
                    if ($planet) $planet->metal += $qty;
                    $bits[] = "+{$qty} M";
                    break;
                case self::RESOURCE_CRYSTAL:
                    if ($planet) $planet->crystal += $qty;
                    $bits[] = "+{$qty} K";
                    break;
                case self::RESOURCE_DEUTERIUM:
                    if ($planet) $planet->deuterium += $qty;
                    $bits[] = "+{$qty} D";
                    break;
                case self::RESOURCE_DARK_MATTER:
                    $user->dark_matter += $qty;
                    $bits[] = "+{$qty} DM";
                    break;
                case self::RESOURCE_ENERGY:
                    // Energy is a derived stat, not stockpiled. Skip.
                    break;
            }
        }
        if ($planet) $planet->save();
        $user->save();

        return implode(' &nbsp; ', $bits);
    }

    /**
     * Count tasks in the chapter that are completed but not yet collected.
     * Used by the side-panel hint badge (.ipiHintCollect).
     */
    private function countUnclaimedRewardsForChapter(int $userId, int $chapterId): int
    {
        return IpiPlayerProgress::where('user_id', $userId)
            ->where('state', IpiPlayerProgress::STATE_COMPLETED)
            ->whereIn('task_id', function ($q) use ($chapterId) {
                $q->select('id')->from('ipi_tasks')->where('chapter_id', $chapterId);
            })
            ->count();
    }
}

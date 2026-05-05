<?php

namespace OGame\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OGame\Models\IpiPlayerProgress;
use OGame\Models\IpiTask;
use OGame\Models\IpiTaskAction;
use OGame\Models\Planet;
use OGame\Models\User;
use OGame\Models\UserTech;

/**
 * Recomputes IPI task progress for a player by inspecting the live game state
 * (buildings, research, ship/defense counts, production settings).
 *
 * Called as a one-shot backfill via `php artisan ipi:backfill-progress` and
 * (optionally, via M3.5+) from event hooks when relevant state changes.
 *
 * Coverage matrix:
 *   building_level        → planets.<machine_name>           (auto)
 *   research_level        → users_tech.<machine_name>        (auto)
 *   ship_built            → planets.<machine_name>           (auto, cumulative-as-current)
 *   defense_built         → planets.<machine_name>           (auto, cumulative-as-current)
 *   production_setting    → planets.<machine_name>_percent   (auto, home planet only)
 *   event                 → MANUAL (requires explicit hook in respective service)
 *   lifeform_*            → SKIP (feature not implemented yet)
 *   demolish              → SKIP (requires history tracking)
 *
 * The "cumulative-as-current" approximation means a player who built then scrapped
 * a Light Fighter will appear NOT to have completed the task. This is wrong but
 * cheap. Proper tracking would require a per-user counter table.
 */
class IpiProgressService
{
    /** Cache TTL for the per-user "recompute already ran recently" flag. */
    private const RECOMPUTE_CACHE_TTL_SECONDS = 30;

    /**
     * Returns the cache key used to short-circuit redundant recomputes.
     * Invalidated by triggerEvent() so any state change forces a fresh recompute
     * on the next page render.
     */
    private function recomputeCacheKey(int $userId): string
    {
        return 'ipi_recompute_done:' . $userId;
    }

    /**
     * Recompute progress for every (non-collected) task of the given user.
     * Returns the number of progress rows touched.
     *
     * Cached: a successful recompute marks the user as "fresh" for 30s. Subsequent
     * calls within that window are no-ops (saves ~50ms/page render). triggerEvent()
     * busts this cache so any imperative event forces a fresh recompute next time.
     *
     * @param bool $force Bypass the cache and always recompute.
     */
    public function recompute(int $userId, bool $force = false): int
    {
        if (!$force && Cache::has($this->recomputeCacheKey($userId))) {
            return 0;
        }
        $user = User::find($userId);
        if (!$user) return 0;

        // Auto-collect welcome task on first recompute for this player. Idempotent.
        app(IpiOverviewService::class)->ensureWelcomeCollected($userId);

        $userTech = UserTech::where('user_id', $userId)->first();
        // Only "Planet" type rows count (exclude moons / debris / deep space).
        $allPlanets = Planet::where('user_id', $userId)->where('planet_type', 1)->orderBy('id')->get();
        // Home planet = the first (lowest id) regular planet — the "pianeta madre".
        // planet_current may be null on freshly registered players, so we don't rely on it.
        $homePlanet = $allPlanets->first();
        $colonies = $allPlanets->slice(1);

        // Only tasks of enabled chapters — skip disabled (e.g. Cap VI lifeform).
        $tasks = IpiTask::with('actions')
            ->whereHas('chapter', fn($q) => $q->where('enabled', true))
            ->get();
        $touched = 0;

        DB::transaction(function () use ($userId, $tasks, $userTech, $homePlanet, $colonies, $allPlanets, &$touched) {
            foreach ($tasks as $task) {
                if ($task->id === IpiOverviewService::WELCOME_TASK_ID) continue;

                $row = IpiPlayerProgress::firstOrNew(['user_id' => $userId, 'task_id' => $task->id]);
                $row->state ??= IpiPlayerProgress::STATE_NONE;
                if ($row->state === IpiPlayerProgress::STATE_COLLECTED) continue;

                // Count satisfied actions in sort_order
                $satisfied = 0;
                foreach ($task->actions as $action) {
                    if ($this->isActionSatisfied($action, $userTech, $homePlanet, $colonies, $allPlanets)) {
                        $satisfied++;
                    } else {
                        // Sequential: first unsatisfied stops the count.
                        break;
                    }
                }

                $previousProgress = (int)$row->progress_count;
                $previousState = $row->state ?? IpiPlayerProgress::STATE_NONE;
                $row->progress_count = $satisfied;

                // State transitions
                if ($satisfied >= $task->total_steps) {
                    if ($row->state !== IpiPlayerProgress::STATE_COLLECTED) {
                        $row->state = IpiPlayerProgress::STATE_COMPLETED;
                        $row->completed_at ??= now();
                    }
                } elseif ($row->state === IpiPlayerProgress::STATE_COMPLETED) {
                    // Should not happen unless an action was undone; revert.
                    $row->state = IpiPlayerProgress::STATE_TRACKED === $previousState
                        ? IpiPlayerProgress::STATE_TRACKED
                        : IpiPlayerProgress::STATE_NONE;
                }

                if ($row->isDirty()) {
                    $row->save();
                    $touched++;
                }
            }
        });

        // Mark user as freshly recomputed; subsequent calls within TTL are no-ops.
        Cache::put($this->recomputeCacheKey($userId), 1, self::RECOMPUTE_CACHE_TTL_SECONDS);

        return $touched;
    }

    /**
     * Centralised entry-point for "event" type IPI actions (e.g. alliance_join,
     * planet_rename, merchant_called). Called from the relevant project services
     * when the corresponding action happens.
     *
     * Behaviour:
     *   - Finds every IpiTaskAction with requirement_type='event' and matching
     *     requirement_target. If multiple actions on the same task have the same
     *     event key (rare), only the first uncompleted one progresses.
     *   - Honours requirement_value (e.g. mission_transport_send wants position 16)
     *     and planet_scope (e.g. planet_rename home_planet vs colonized_planet).
     *   - Increments progress_count on the parent task and transitions to 'completed'
     *     if total_steps reached.
     *
     * @param int    $userId
     * @param string $eventKey  e.g. 'alliance_join', 'planet_rename'
     * @param array  $context   Optional: keys 'value' (matches requirement_value) and 'scope' (matches planet_scope)
     */
    public function triggerEvent(int $userId, string $eventKey, array $context = []): void
    {
        $actions = IpiTaskAction::query()
            ->where('requirement_type', 'event')
            ->where('requirement_target', $eventKey)
            ->whereHas('task.chapter', fn($q) => $q->where('enabled', true))
            ->orderBy('task_id')
            ->orderBy('sort_order')
            ->get();

        if ($actions->isEmpty()) return;

        // State change incoming → bust the recompute cache so the next page render
        // sees fresh data (otherwise the 30s freshness window could mask the event).
        Cache::forget($this->recomputeCacheKey($userId));

        foreach ($actions as $action) {
            // Match requirement_value if specified
            if ($action->requirement_value !== null && isset($context['value'])
                && (int)$context['value'] !== (int)$action->requirement_value) {
                continue;
            }
            // Match planet_scope if specified
            if ($action->planet_scope !== null && $action->planet_scope !== 'any'
                && isset($context['scope']) && $action->planet_scope !== $context['scope']) {
                continue;
            }
            $this->markActionCompleted($userId, $action);
        }
    }

    /**
     * Marks a single action as completed on the player's progress row. Sequential
     * model: this only advances progress_count if the action's sort_order matches
     * the current progress_count (i.e. it's the next pending action of the task).
     */
    private function markActionCompleted(int $userId, IpiTaskAction $action): void
    {
        $task = IpiTask::find($action->task_id);
        if (!$task) return;

        DB::transaction(function () use ($userId, $task, $action) {
            $row = IpiPlayerProgress::firstOrNew(['user_id' => $userId, 'task_id' => $task->id]);
            $row->state ??= IpiPlayerProgress::STATE_NONE;
            if ($row->state === IpiPlayerProgress::STATE_COLLECTED) return;

            $progress = (int)$row->progress_count;
            // Only advance if this is the next pending action (sequential).
            if ($progress > $action->sort_order) return;
            if ($progress < $action->sort_order) {
                // An earlier action is not yet completed — don't skip ahead.
                return;
            }
            $row->progress_count = $progress + 1;
            if ($row->progress_count >= $task->total_steps) {
                if ($row->state !== IpiPlayerProgress::STATE_COLLECTED) {
                    $row->state = IpiPlayerProgress::STATE_COMPLETED;
                    $row->completed_at ??= now();
                }
            }
            $row->save();
        });
    }

    /**
     * Whether a single IpiTaskAction is currently satisfied for the user.
     */
    public function isActionSatisfied(
        IpiTaskAction $action,
        ?UserTech $userTech,
        ?Planet $homePlanet,
        $colonies,
        $allPlanets
    ): bool {
        $type = $action->requirement_type;
        $target = $action->requirement_target;
        $value = (int)($action->requirement_value ?? 0);
        $scope = $action->planet_scope;

        if (!$type) return false;

        return match ($type) {
            'building_level' => $this->checkBuildingLevel($target, $value, $scope, $homePlanet, $colonies, $allPlanets),
            'research_level' => $userTech && $target && (int)($userTech->{$target} ?? 0) >= $value,
            'ship_built', 'defense_built' => $this->checkUnitCount($target, $value, $allPlanets),
            'production_setting' => $this->checkProductionSetting($target, $value, $homePlanet),
            'event' => $this->checkPassiveEvent($target, $homePlanet),
            // 'lifeform_*', 'demolish' → manual / skipped
            default => false,
        };
    }

    private function checkBuildingLevel(?string $machineName, int $reqLevel, ?string $scope, ?Planet $homePlanet, $colonies, $allPlanets): bool
    {
        if (!$machineName) return false;
        $planets = match ($scope) {
            'home_planet' => $homePlanet ? collect([$homePlanet]) : collect(),
            'colonized_planet' => $colonies,
            default => $allPlanets,
        };
        foreach ($planets as $p) {
            if ((int)($p->{$machineName} ?? 0) >= $reqLevel) return true;
        }
        return false;
    }

    private function checkUnitCount(?string $machineName, int $reqQty, $allPlanets): bool
    {
        if (!$machineName) return false;
        $total = 0;
        foreach ($allPlanets as $p) {
            $total += (int)($p->{$machineName} ?? 0);
        }
        return $total >= $reqQty;
    }

    private function checkProductionSetting(?string $machineName, int $reqPercent, ?Planet $homePlanet): bool
    {
        if (!$machineName || !$homePlanet) return false;
        $col = $machineName . '_percent';
        return (int)($homePlanet->{$col} ?? -1) === $reqPercent;
    }

    /**
     * Subset of "event" actions that are satisfied by inspecting current state alone
     * (no hook needed). Most "event" actions are imperative (alliance_join, planet_rename,
     * ...) and are only triggered via triggerEvent(); but a few — like energy_positive —
     * are state-based and can be re-evaluated on every recompute.
     */
    private function checkPassiveEvent(?string $eventKey, ?Planet $homePlanet): bool
    {
        return match ($eventKey) {
            'energy_positive' => $homePlanet
                && ((int)$homePlanet->energy_max - (int)$homePlanet->energy_used) >= 0,
            default => false,
        };
    }
}

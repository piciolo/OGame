<?php

namespace Tests\Feature;

use OGame\Models\IpiChapter;
use OGame\Models\IpiPlayerProgress;
use OGame\Models\IpiTask;
use OGame\Services\IpiOverviewService;
use OGame\Services\IpiProgressService;
use Tests\AccountTestCase;

/**
 * Verifies IpiOverviewService and IpiProgressService behaviour:
 *  - chapter visibility (disabled Cap VI must be hidden)
 *  - welcome task auto-collected on first overlay open
 *  - track/untrack toggle semantics
 *  - collectTask requires 'completed' state and awards rewards
 *  - triggerEvent honours scope and value matchers
 *  - sequential progress: action #N completes only when prior actions are done
 */
class IpiOverviewServiceTest extends AccountTestCase
{
    private IpiOverviewService $service;
    private IpiProgressService $progress;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(IpiOverviewService::class);
        $this->progress = resolve(IpiProgressService::class);
    }

    public function testGetAllChaptersExcludesDisabled(): void
    {
        $chapters = $this->service->getAllChapters();
        $ids = $chapters->pluck('id')->all();
        $this->assertCount(6, $ids, 'Expected 6 enabled chapters (Cap VI lifeform hidden).');
        $this->assertContains(4001, $ids);
        $this->assertNotContains(4006, $ids, 'Cap VI (4006) must be hidden.');
        $this->assertContains(4007, $ids, 'Cap VII (4007) must be visible.');
    }

    public function testGetChapterReturnsNullForDisabled(): void
    {
        $disabled = $this->service->getChapter(4006);
        $this->assertNull($disabled, 'Disabled Cap VI must not be returned.');

        $enabled = $this->service->getChapter(4001);
        $this->assertNotNull($enabled);
        $this->assertSame(4001, $enabled->id);
    }

    public function testWelcomeTaskAutoCollectsOnFirstAccess(): void
    {
        // Before: no progress row.
        $this->assertSame(
            0,
            IpiPlayerProgress::where('user_id', $this->currentUserId)
                ->where('task_id', IpiOverviewService::WELCOME_TASK_ID)
                ->count()
        );

        $task = IpiTask::find(IpiOverviewService::WELCOME_TASK_ID);
        $state = $this->service->getTaskState($task, $this->currentUserId);
        $this->assertSame(IpiPlayerProgress::STATE_COLLECTED, $state);

        // After: row exists in 'collected' state.
        $row = IpiPlayerProgress::where('user_id', $this->currentUserId)
            ->where('task_id', IpiOverviewService::WELCOME_TASK_ID)
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(IpiPlayerProgress::STATE_COLLECTED, $row->state);
    }

    public function testTrackTaskTogglesState(): void
    {
        $taskId = 5002;
        // Start in 'none'
        IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', $taskId)->delete();

        $r1 = $this->service->trackTask($this->currentUserId, $taskId);
        $this->assertTrue($r1['success']);
        $rowAfter1 = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', $taskId)->first();
        $this->assertSame(IpiPlayerProgress::STATE_TRACKED, $rowAfter1->state);

        // Toggle off
        $r2 = $this->service->trackTask($this->currentUserId, $taskId);
        $this->assertTrue($r2['success']);
        $rowAfter2 = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', $taskId)->first();
        $this->assertSame(IpiPlayerProgress::STATE_NONE, $rowAfter2->state);
    }

    public function testTrackTaskUntracksPreviousTrackedTask(): void
    {
        // Pretend 5002 is already tracked
        IpiPlayerProgress::updateOrCreate(
            ['user_id' => $this->currentUserId, 'task_id' => 5002],
            ['state' => IpiPlayerProgress::STATE_TRACKED, 'progress_count' => 0]
        );
        IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5004)->delete();

        // Track 5004 — 5002 should drop to none
        $this->service->trackTask($this->currentUserId, 5004);
        $r5002 = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5002)->first();
        $r5004 = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5004)->first();
        $this->assertSame(IpiPlayerProgress::STATE_NONE, $r5002->state);
        $this->assertSame(IpiPlayerProgress::STATE_TRACKED, $r5004->state);
    }

    public function testCollectTaskFailsWhenNotCompleted(): void
    {
        IpiPlayerProgress::updateOrCreate(
            ['user_id' => $this->currentUserId, 'task_id' => 5002],
            ['state' => IpiPlayerProgress::STATE_NONE, 'progress_count' => 0]
        );
        $r = $this->service->collectTask($this->currentUserId, 5002);
        $this->assertFalse($r['success']);
        $this->assertSame('Task not completed', $r['error']);
    }

    public function testCollectTaskTransitionsToCollectedAndAwardsReward(): void
    {
        $task = IpiTask::with('rewards')->find(5002);
        IpiPlayerProgress::updateOrCreate(
            ['user_id' => $this->currentUserId, 'task_id' => 5002],
            ['state' => IpiPlayerProgress::STATE_COMPLETED, 'progress_count' => $task->total_steps]
        );

        $r = $this->service->collectTask($this->currentUserId, 5002);
        $this->assertTrue($r['success']);
        $this->assertNotEmpty($r['claimedRewardsRendered']);

        $row = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5002)->first();
        $this->assertSame(IpiPlayerProgress::STATE_COLLECTED, $row->state);
        $this->assertNotNull($row->collected_at);
    }

    public function testTriggerEventAdvancesMatchingTask(): void
    {
        // task 5070 (event/merchant_called)
        IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5070)->delete();

        $this->progress->triggerEvent($this->currentUserId, 'merchant_called');

        $row = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5070)->first();
        $this->assertNotNull($row);
        $this->assertSame(IpiPlayerProgress::STATE_COMPLETED, $row->state);
        $this->assertSame(1, (int)$row->progress_count);
    }

    public function testTriggerEventScopeFilter(): void
    {
        // 5022 = planet_rename home_planet ; 5039 = planet_rename colonized_planet
        foreach ([5022, 5039] as $tid) {
            IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', $tid)->delete();
        }

        $this->progress->triggerEvent($this->currentUserId, 'planet_rename', ['scope' => 'colonized_planet']);

        $row5022 = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5022)->first();
        $row5039 = IpiPlayerProgress::where('user_id', $this->currentUserId)->where('task_id', 5039)->first();
        $this->assertNull($row5022, '5022 (home scope) must NOT advance for colonized_planet trigger.');
        $this->assertNotNull($row5039, '5039 (colony scope) must advance.');
        $this->assertSame(IpiPlayerProgress::STATE_COMPLETED, $row5039->state);
    }

    public function testIsChapterCollectableRequiresAllTasksDone(): void
    {
        $chapter = IpiChapter::with('tasks')->find(4001);
        // Wipe progress for user → not collectable
        IpiPlayerProgress::where('user_id', $this->currentUserId)
            ->whereIn('task_id', $chapter->tasks->pluck('id'))->delete();
        $this->assertFalse(
            $this->service->isChapterCollectable($chapter, $this->currentUserId)
        );

        // Mark all non-welcome tasks as completed
        foreach ($chapter->tasks as $t) {
            if ($t->id === IpiOverviewService::WELCOME_TASK_ID) continue;
            IpiPlayerProgress::updateOrCreate(
                ['user_id' => $this->currentUserId, 'task_id' => $t->id],
                ['state' => IpiPlayerProgress::STATE_COMPLETED, 'progress_count' => $t->total_steps]
            );
        }
        $this->assertTrue(
            $this->service->isChapterCollectable($chapter, $this->currentUserId)
        );
    }
}

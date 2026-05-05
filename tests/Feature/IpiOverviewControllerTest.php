<?php

namespace Tests\Feature;

use OGame\Models\IpiPlayerProgress;
use OGame\Models\IpiTask;
use OGame\Services\IpiOverviewService;
use Tests\AccountTestCase;

/**
 * HTTP end-to-end tests for the "Panoramica direttive" (IPI) controller:
 *  - GET /ajax/ipioverview/overlay returns 200 and contains the requested chapter
 *  - overlay falls back to the default chapter when chapterId is unknown/disabled
 *  - track-task / collect-task / collect-chapter reject calls without a CSRF token
 *  - CSRF failure response includes a newAjaxToken so the frontend can recover
 *  - track-task with a valid token toggles persistence
 *  - collect-task on a completed task transitions to 'collected'
 *  - collect-task on a non-completed task fails with the documented error string
 *
 * Note: the controller exposes JSON via Content-Type: text/plain (so the original
 * IPI module's JSON.parse() works); we therefore decode the raw body manually.
 */
class IpiOverviewControllerTest extends AccountTestCase
{
    public function testOverlayReturnsTwoHundredAndDefaultChapter(): void
    {
        $response = $this->get(route('ipioverview.overlay'));
        $response->assertStatus(200);
        $response->assertViewIs('ingame.ipioverview.overlay');
        $response->assertViewHas('chapter');

        $chapter = $response->viewData('chapter');
        $this->assertNotNull($chapter);
        $this->assertSame(IpiOverviewService::DEFAULT_CHAPTER_ID, (int) $chapter->id);
    }

    public function testOverlayHonoursExplicitChapterIdQueryParam(): void
    {
        $response = $this->get(route('ipioverview.overlay', ['chapterId' => 4002]));
        $response->assertStatus(200);
        $chapter = $response->viewData('chapter');
        $this->assertNotNull($chapter);
        $this->assertSame(4002, (int) $chapter->id);
    }

    public function testOverlayFallsBackToDefaultChapterForUnknownId(): void
    {
        // 4006 is the disabled lifeform chapter (per IpiOverviewServiceTest expectations).
        $response = $this->get(route('ipioverview.overlay', ['chapterId' => 4006]));
        $response->assertStatus(200);
        $chapter = $response->viewData('chapter');
        $this->assertNotNull($chapter);
        $this->assertSame(IpiOverviewService::DEFAULT_CHAPTER_ID, (int) $chapter->id);
    }

    public function testTrackTaskWithoutTokenIsCsrfRejected(): void
    {
        $response = $this->get(route('ipioverview.tracktask', ['taskId' => 5002]));
        $response->assertStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertFalse($payload['success']);
        $this->assertSame('CSRF token mismatch', $payload['error']);
        $this->assertNotEmpty($payload['newAjaxToken'], 'A fresh token must be returned for the client to retry.');
    }

    public function testCollectTaskWithoutTokenIsCsrfRejected(): void
    {
        $response = $this->get(route('ipioverview.collecttask', ['taskId' => 5002]));
        $response->assertStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('CSRF token mismatch', $payload['error']);
    }

    public function testCollectChapterWithoutTokenIsCsrfRejected(): void
    {
        $response = $this->get(route('ipioverview.collectchapter', ['chapterId' => 4001]));
        $response->assertStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('CSRF token mismatch', $payload['error']);
    }

    public function testTrackTaskWithValidTokenTogglesProgressRow(): void
    {
        $taskId = 5002;
        IpiPlayerProgress::query()
            ->where('user_id', $this->currentUserId)
            ->where('task_id', $taskId)
            ->delete();

        $response = $this->get(route('ipioverview.tracktask', [
            'taskId' => $taskId,
            'token'  => csrf_token(),
        ]));
        $response->assertStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['success']);

        $row = IpiPlayerProgress::query()
            ->where('user_id', $this->currentUserId)
            ->where('task_id', $taskId)
            ->first();
        $this->assertNotNull($row);
        $this->assertSame(IpiPlayerProgress::STATE_TRACKED, $row->state);
    }

    public function testCollectTaskRejectsWhenNotCompleted(): void
    {
        $taskId = 5002;
        IpiPlayerProgress::updateOrCreate(
            ['user_id' => $this->currentUserId, 'task_id' => $taskId],
            ['state' => IpiPlayerProgress::STATE_NONE, 'progress_count' => 0]
        );

        $response = $this->get(route('ipioverview.collecttask', [
            'taskId' => $taskId,
            'token'  => csrf_token(),
        ]));
        $response->assertStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame('Task not completed', $payload['error']);
    }

    public function testCollectTaskTransitionsCompletedToCollected(): void
    {
        $taskId = 5002;
        $task = IpiTask::find($taskId);
        IpiPlayerProgress::updateOrCreate(
            ['user_id' => $this->currentUserId, 'task_id' => $taskId],
            ['state' => IpiPlayerProgress::STATE_COMPLETED, 'progress_count' => $task->total_steps]
        );

        $response = $this->get(route('ipioverview.collecttask', [
            'taskId' => $taskId,
            'token'  => csrf_token(),
        ]));
        $response->assertStatus(200);

        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['success']);

        $row = IpiPlayerProgress::query()
            ->where('user_id', $this->currentUserId)
            ->where('task_id', $taskId)
            ->first();
        $this->assertSame(IpiPlayerProgress::STATE_COLLECTED, $row->state);
        $this->assertNotNull($row->collected_at);
    }

    public function testJsonResponsesUseTextPlainContentType(): void
    {
        $response = $this->get(route('ipioverview.tracktask', ['taskId' => 5002]));
        $response->assertStatus(200);
        // The original IPI module relies on JSON.parse(data); only text/plain reaches the
        // module raw, so this header must NOT be application/json.
        $this->assertStringStartsWith('text/plain', (string) $response->headers->get('Content-Type'));
    }
}

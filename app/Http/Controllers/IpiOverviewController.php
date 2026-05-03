<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use OGame\Services\IpiOverviewService;

/**
 * Backs the "Panoramica direttive" overlay.
 *
 * The trackTask/collectTask/collectChapter responses use Content-Type: text/plain so
 * that the OGame-original IPI module's `JSON.parse(data)` call works (a Laravel
 * JsonResponse would be auto-parsed by jQuery, leaving an object that JSON.parse
 * chokes on).
 */
class IpiOverviewController extends OGameController
{
    public function __construct(private readonly IpiOverviewService $ipi)
    {
    }

    public function overlay(Request $request, \OGame\Services\IpiProgressService $progress): View
    {
        $uid = auth()->id();
        if ($uid) $progress->recompute($uid);

        $chapterId = (int)$request->query('chapterId', IpiOverviewService::DEFAULT_CHAPTER_ID);
        $chapter = $this->ipi->getChapter($chapterId) ?? $this->ipi->getChapter(IpiOverviewService::DEFAULT_CHAPTER_ID);

        return view('ingame.ipioverview.overlay', [
            'chapter' => $chapter,
            'allChapters' => $this->ipi->getAllChapters(),
            'service' => $this->ipi,
            'progressMap' => $this->ipi->getProgressMap($uid),
        ]);
    }

    public function trackTask(Request $request): Response
    {
        if (!$this->verifyCsrfFromQuery($request)) return $this->csrfFailure();
        $taskId = (int)$request->query('taskId');
        return $this->jsonAsText($this->ipi->trackTask(auth()->id(), $taskId));
    }

    public function collectTask(Request $request): Response
    {
        if (!$this->verifyCsrfFromQuery($request)) return $this->csrfFailure();
        $taskId = (int)$request->query('taskId');
        return $this->jsonAsText($this->ipi->collectTask(auth()->id(), $taskId));
    }

    public function collectChapter(Request $request): Response
    {
        if (!$this->verifyCsrfFromQuery($request)) return $this->csrfFailure();
        $chapterId = (int)$request->query('chapterId');
        return $this->jsonAsText($this->ipi->collectChapter(auth()->id(), $chapterId));
    }

    /**
     * Manually validates the CSRF token passed in the query string by the
     * OGame-original IPI module (which appends "&token=NNN" to GET URLs).
     * Laravel's VerifyCsrfToken middleware skips GET, so the validation
     * happens here. Comparison is constant-time to avoid timing attacks.
     */
    private function verifyCsrfFromQuery(Request $request): bool
    {
        $supplied = (string)$request->query('token', '');
        $expected = (string)csrf_token();
        if ($supplied === '' || $expected === '') return false;
        return hash_equals($expected, $supplied);
    }

    /**
     * Response for CSRF mismatch. Returns the current token under newAjaxToken
     * so the IPI module can pick it up and retry on the next call.
     */
    private function csrfFailure(): Response
    {
        return $this->jsonAsText([
            'success' => false,
            'error' => 'CSRF token mismatch',
            'newAjaxToken' => csrf_token(),
        ]);
    }

    private function jsonAsText(array $payload): Response
    {
        return response((string)json_encode($payload), 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }
}

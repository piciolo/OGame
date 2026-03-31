<?php

namespace OGame\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use OGame\Services\DarkMatterService;
use OGame\Services\OfficerService;

class PremiumController extends OGameController
{
    public function __construct(
        private OfficerService $officerService,
        private DarkMatterService $darkMatterService
    ) {
        parent::__construct();
    }

    /**
     * Shows the premium/officers index page.
     */
    public function index(): View
    {
        $this->setBodyId('premium');

        $user = Auth::user();
        $officer = $this->officerService->getOfficer($user);

        return view('ingame.premium.index', [
            'darkMatter' => $user->dark_matter ?? 0,
            'officer' => $officer,
        ]);
    }

    /**
     * AJAX: return officer detail HTML for the slide-in panel.
     */
    public function ajax(Request $request): string
    {
        $typeId = (int) $request->input('type', 0);
        $user = Auth::user();
        $officer = $this->officerService->getOfficer($user);

        // Type 1 = Dark Matter info (no purchase)
        if ($typeId === 1) {
            return view('ingame.premium.detail-darkmatter', [
                'darkMatter' => $user->dark_matter ?? 0,
            ])->render();
        }

        $officerKey = $this->officerService->getKeyFromTypeId($typeId);
        if ($officerKey === null) {
            return '';
        }

        $isActive = $officer->isOfficerActive($officerKey);
        $column = $officerKey . '_until';
        $expiresAt = $officer->$column;

        $costs = OfficerService::COSTS[$officerKey] ?? [];

        return view('ingame.premium.detail-officer', [
            'officerKey' => $officerKey,
            'typeId' => $typeId,
            'isActive' => $isActive,
            'expiresAt' => $expiresAt,
            'costs' => $costs,
            'darkMatter' => $user->dark_matter ?? 0,
        ])->render();
    }

    /**
     * POST: purchase/activate an officer.
     */
    public function purchase(Request $request): JsonResponse
    {
        $typeId = (int) $request->input('type');
        $days = (int) $request->input('duration');
        $user = Auth::user();

        $officerKey = $this->officerService->getKeyFromTypeId($typeId);
        if ($officerKey === null) {
            return response()->json(['success' => false, 'error' => 'Invalid officer type.']);
        }

        if (!in_array($days, OfficerService::DURATIONS, true)) {
            return response()->json(['success' => false, 'error' => 'Invalid duration.']);
        }

        $cost = $this->officerService->getCost($officerKey, $days);
        if (!$this->darkMatterService->canAfford($user, $cost)) {
            return response()->json(['success' => false, 'error' => __('t_ingame.premium.insufficient_dark_matter')]);
        }

        try {
            $this->officerService->purchase($user, $officerKey, $days);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }

        // Refresh user to get updated dark matter
        $user->refresh();

        return response()->json([
            'success' => true,
            'newBalance' => $user->dark_matter,
        ]);
    }
}

<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Factories\PlayerServiceFactory;
use OGame\Services\AchievementService;
use OGame\Services\PlayerProfileService;
use OGame\Services\PlayerService;

class PlayerProfileController extends OGameController
{
    public function index(
        Request $request,
        PlayerService $currentPlayer,
        PlayerProfileService $profileService,
        AchievementService $achievementService,
        PlayerServiceFactory $playerFactory,
    ): View {
        $this->setBodyId('playerprofile');

        $profileId = (int) $request->input('profileId', $currentPlayer->getId());
        if ($profileId <= 0) {
            $profileId = $currentPlayer->getId();
        }

        $isOwner = ($profileId === $currentPlayer->getId());
        $targetPlayer = $isOwner ? $currentPlayer : $playerFactory->make($profileId);

        // Visibility check (owners always see their own profile).
        $visible = $isOwner || (bool) ($targetPlayer->getUser()->profile_visible ?? true);
        $achievementsVisible = $isOwner || (bool) ($targetPlayer->getUser()->achievements_visible ?? true);

        return view('ingame.playerprofile.index')->with([
            'isOwner' => $isOwner,
            'visible' => $visible,
            'achievementsVisible' => $achievementsVisible,
            'targetPlayer' => $targetPlayer,
            'profileEntries' => $visible ? $profileService->getProfileEntries($targetPlayer) : [],
            'moreInfoEntries' => $visible ? $profileService->getMoreInfoEntries($targetPlayer) : [],
            'availableTags' => $isOwner ? $profileService->getAvailableTags($targetPlayer) : [],
            'achievements' => $achievementsVisible ? $achievementService->getAchievementsForPlayer($targetPlayer) : collect(),
            'unlockedAvatars' => $achievementsVisible ? $achievementService->getUnlockedAvatars($targetPlayer) : [],
            'unlockedSkins' => $achievementsVisible ? $achievementService->getUnlockedSkins($targetPlayer) : [],
            'unlockedTitles' => $achievementsVisible ? $achievementService->getUnlockedTitles($targetPlayer) : [],
            'rewardCatalog' => $achievementsVisible ? $achievementService->getRewardCatalog() : ['avatar' => [], 'skin' => [], 'title' => []],
        ]);
    }

    /**
     * Save user-selected profile tags (order preserved, invalid filtered).
     * Payload: {"profile": [...data-types], "moreInfo": [...data-types]}
     */
    public function tags(Request $request, PlayerService $currentPlayer, PlayerProfileService $profileService): JsonResponse
    {
        $profile = $request->input('profile', []);
        $moreInfo = $request->input('moreInfo', []);
        if (!is_array($profile) || !is_array($moreInfo)) {
            return response()->json(['success' => false, 'error' => 'invalid_payload'], 422);
        }
        $profileService->saveSelectedTags(
            $currentPlayer,
            array_map('strval', $profile),
            array_map('strval', $moreInfo),
        );

        return response()->json(['success' => true]);
    }

    /**
     * Save player gender used for profile title (male/female).
     */
    public function gender(Request $request, PlayerService $currentPlayer): JsonResponse
    {
        $gender = (string) $request->input('gender', '');
        if (!in_array($gender, ['male', 'female'], true)) {
            return response()->json(['success' => false, 'error' => 'invalid_gender'], 422);
        }
        $user = $currentPlayer->getUser();
        $user->profile_gender = $gender;
        $user->save();
        return response()->json(['success' => true]);
    }

    /**
     * Imposta avatar/skin/title come selezione corrente del profilo del giocatore.
     * Richiede che la ricompensa sia gia' sbloccata (player_unlocked_*).
     */
    public function selectReward(Request $request, PlayerService $currentPlayer, AchievementService $achievementService): JsonResponse
    {
        $type = (string) $request->input('type', '');
        $machineName = (string) $request->input('machine_name', '');

        if (!in_array($type, ['avatar', 'skin', 'title'], true)) {
            return response()->json(['success' => false, 'error' => 'invalid_type'], 422);
        }
        if ($machineName === '') {
            return response()->json(['success' => false, 'error' => 'invalid_machine_name'], 422);
        }
        if (!$achievementService->isUnlocked($currentPlayer, $type, $machineName)) {
            return response()->json(['success' => false, 'error' => 'reward_locked'], 403);
        }

        $user = $currentPlayer->getUser();
        match ($type) {
            'avatar' => $user->profile_avatar = $machineName,
            'skin' => $user->profile_planet_skin = $machineName,
            'title' => $user->profile_title = $machineName,
        };
        $user->save();

        return response()->json(['success' => true, 'type' => $type, 'machine_name' => $machineName]);
    }

    /**
     * Toggle visibility flags (profile_visible, achievements_visible, global_profile).
     */
    public function visibility(Request $request, PlayerService $currentPlayer): JsonResponse
    {
        $field = (string) $request->input('field', '');
        $value = (bool) $request->boolean('value');

        $allowed = ['profile_visible', 'achievements_visible', 'global_profile'];
        if (!in_array($field, $allowed, true)) {
            return response()->json(['success' => false, 'error' => 'invalid_field'], 422);
        }

        $user = $currentPlayer->getUser();
        $user->{$field} = $value;
        $user->save();

        return response()->json(['success' => true, 'field' => $field, 'value' => $value]);
    }
}

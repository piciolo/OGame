<?php

namespace OGame\Services;

use OGame\Enums\HighscoreTypeEnum;
use OGame\Enums\ProfileTagEnum;
use OGame\Models\AchievementTier;
use OGame\Models\Alliance;
use OGame\Models\AllianceHighscore;
use OGame\Models\Highscore;
use OGame\Models\FleetMission;

class PlayerProfileService
{
    public function __construct(
        private PlayerRankHistoryService $rankHistoryService,
        private HighscoreService $highscoreService,
    ) {}

    /** Slot fissi per sezione (replica del comportamento OGame ufficiale). */
    public const PROFILE_SLOTS = 9;
    public const MOREINFO_SLOTS = 12;

    /**
     * Risolve il testo titolo a partire dal machine_name (es. A1_T4_Tit_ID1
     * → "Talento eccezionale"). Sorgente: achievement_tiers.title_text.
     */
    private function resolveTitleText(string $machineName): string
    {
        if ($machineName === '') {
            return '';
        }
        return (string) (AchievementTier::where('reward_machine_name', $machineName)
            ->where('reward_type', 'title')
            ->value('title_text') ?? '');
    }

    /**
     * Ritorna esattamente PROFILE_SLOTS voci. Le voci selezionate vanno
     * all'inizio, gli slot non utilizzati sono `['type' => 'empty']`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProfileEntries(PlayerService $player): array
    {
        $hsRow = $this->getHighscoreRow($player->getId());
        $entries = [];
        foreach ($this->getSelectedTags($player, 'profile') as $tag) {
            $entries[] = $this->buildEntryForTag($player, $tag, $hsRow);
        }
        return $this->padToSlots($entries, self::PROFILE_SLOTS);
    }

    /**
     * Ritorna esattamente MOREINFO_SLOTS voci, padding con slot vuoti.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMoreInfoEntries(PlayerService $player): array
    {
        $hsRow = $this->getHighscoreRow($player->getId());
        $entries = [];
        foreach ($this->getSelectedTags($player, 'moreInfo') as $tag) {
            $entries[] = $this->buildEntryForTag($player, $tag, $hsRow);
        }
        return $this->padToSlots($entries, self::MOREINFO_SLOTS);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @return array<int, array<string, mixed>>
     */
    private function padToSlots(array $entries, int $totalSlots): array
    {
        $entries = array_slice($entries, 0, $totalSlots);
        while (count($entries) < $totalSlots) {
            $entries[] = ['type' => 'empty', 'tag' => '', 'label' => '', 'value' => null, 'removable' => false];
        }
        return $entries;
    }

    /**
     * @return list<array{tag:ProfileTagEnum, label:string, entry:array}>
     */
    public function getAvailableTags(PlayerService $player): array
    {
        $selected = $this->getAllSelectedValues($player);
        $hsRow = $this->getHighscoreRow($player->getId());
        // Lista flat: l'utente può piazzare qualsiasi tag in qualsiasi sezione.
        $available = [];
        foreach (ProfileTagEnum::cases() as $tag) {
            if (!$tag->removable()) {
                continue;
            }
            if (in_array($tag->value, $selected, true)) {
                continue;
            }
            $available[] = [
                'tag' => $tag,
                'label' => __($tag->langKey()),
                'entry' => $this->buildEntryForTag($player, $tag, $hsRow),
            ];
        }
        return $available;
    }

    /**
     * Le pillole non sono vincolate alla `section()` dell'enum: l'utente può
     * posizionare qualsiasi tag in qualsiasi sezione (replica OGame).
     * Solo PlayerTitle resta fisso nella sezione `profile`.
     *
     * @return array<int, ProfileTagEnum>
     */
    public function getSelectedTags(PlayerService $player, string $section): array
    {
        $stored = $player->getUser()->profile_tags;
        if (!is_array($stored) || !isset($stored[$section]) || !is_array($stored[$section])) {
            return $section === 'profile'
                ? ProfileTagEnum::profileDefaults()
                : ProfileTagEnum::moreInfoDefaults();
        }

        $tags = [];
        foreach ($stored[$section] as $value) {
            $tag = ProfileTagEnum::tryFrom((string) $value);
            if ($tag === null) {
                continue;
            }
            // PlayerTitle è ammesso solo in `profile`.
            if ($tag === ProfileTagEnum::PlayerTitle && $section !== 'profile') {
                continue;
            }
            $tags[] = $tag;
        }

        if ($section === 'profile' && !in_array(ProfileTagEnum::PlayerTitle, $tags, true)) {
            array_unshift($tags, ProfileTagEnum::PlayerTitle);
        }

        return $tags ?: ($section === 'profile'
            ? ProfileTagEnum::profileDefaults()
            : ProfileTagEnum::moreInfoDefaults());
    }

    /**
     * @param array<int, string> $profileTags
     * @param array<int, string> $moreInfoTags
     */
    public function saveSelectedTags(PlayerService $player, array $profileTags, array $moreInfoTags): void
    {
        // Tutti i tag sono validi in ogni sezione (eccetto PlayerTitle che è solo in profile).
        $alreadyUsed = [];
        $clean = function (array $values, bool $allowPlayerTitle) use (&$alreadyUsed): array {
            $valid = [];
            foreach ($values as $v) {
                $tag = ProfileTagEnum::tryFrom((string) $v);
                if ($tag === null) continue;
                if ($tag === ProfileTagEnum::PlayerTitle && !$allowPlayerTitle) continue;
                if (in_array($tag->value, $alreadyUsed, true)) continue;
                $valid[] = $tag->value;
                $alreadyUsed[] = $tag->value;
            }
            return $valid;
        };

        $profileClean = $clean($profileTags, true);
        $moreInfoClean = $clean($moreInfoTags, false);

        if (!in_array(ProfileTagEnum::PlayerTitle->value, $profileClean, true)) {
            array_unshift($profileClean, ProfileTagEnum::PlayerTitle->value);
        }

        $profileClean = array_slice($profileClean, 0, self::PROFILE_SLOTS);
        $moreInfoClean = array_slice($moreInfoClean, 0, self::MOREINFO_SLOTS);

        $user = $player->getUser();
        $user->profile_tags = ['profile' => $profileClean, 'moreInfo' => $moreInfoClean];
        $user->save();
    }

    /** @return array<int, string> */
    private function getAllSelectedValues(PlayerService $player): array
    {
        return array_merge(
            array_map(fn(ProfileTagEnum $t) => $t->value, $this->getSelectedTags($player, 'profile')),
            array_map(fn(ProfileTagEnum $t) => $t->value, $this->getSelectedTags($player, 'moreInfo')),
        );
    }

    /**
     * @return array{tag:string,type:string,label:string,value:mixed,removable:bool}
     */
    private function buildEntryForTag(PlayerService $player, ProfileTagEnum $tag, Highscore|null $hsRow): array
    {
        $base = [
            'tag' => $tag->value,
            'removable' => $tag->removable(),
        ];

        return $base + match ($tag) {
            ProfileTagEnum::PlayerTitle => [
                'type' => 'title',
                'label' => '',
                'value' => $player->getUser()->profile_gender ?? 'male',
                'title_machine_name' => $player->getUser()->profile_title ?? '',
                'title_text' => $this->resolveTitleText($player->getUser()->profile_title ?? ''),
            ],
            ProfileTagEnum::HonorDisplay => [
                'type' => 'simple',
                'label' => __('t_ingame.profile.honor'),
                'value' => (int) ($player->getUser()->honor_points ?? 0),
            ],
            ProfileTagEnum::AllianceDisplay => [
                'type' => 'alliance',
                'label' => __('t_ingame.profile.alliance'),
                'value' => $this->buildAllianceValue($player),
            ],
            ProfileTagEnum::CharacterClassDisplay => [
                'type' => 'class',
                'label' => __('t_ingame.profile.class'),
                'value' => $player->getUser()->getCharacterClassEnum()?->value,
            ],
            ProfileTagEnum::AllianceClassDisplay => [
                'type' => 'allianceClass',
                'label' => __('t_ingame.profile.alliance_class'),
                'value' => $this->buildAllianceClassValue($player),
            ],
            ProfileTagEnum::TotalHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.score_total'),
                'value' => $this->buildHighscoreValue($player->getId(), HighscoreTypeEnum::general, $hsRow),
            ],
            ProfileTagEnum::AchievementPointsDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.achievement_points'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::ShipsOwnedDisplay => [
                'type' => 'simple',
                'label' => __('t_ingame.profile.ships_total'),
                'value' => $this->getShipsTotal($player),
            ],
            ProfileTagEnum::LanguageDisplay => [
                'type' => 'simple',
                'label' => __('t_ingame.profile.language'),
                'value' => strtoupper((string) ($player->getUser()->lang ?? '')),
            ],
            ProfileTagEnum::CompletedAchievementsDisplay => [
                'type' => 'simple',
                'label' => __('t_ingame.profile.completed_achievements'),
                'value' => 0, // sistema achievement non implementato
            ],
            ProfileTagEnum::ExpeditionAmountDisplay => [
                'type' => 'simple',
                'label' => __('t_ingame.profile.expedition_amount'),
                'value' => $this->getExpeditionAmount($player),
            ],
            ProfileTagEnum::EconomyHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.score_economy'),
                'value' => $this->buildHighscoreValue($player->getId(), HighscoreTypeEnum::economy, $hsRow),
            ],
            ProfileTagEnum::ResearchHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.score_research'),
                'value' => $this->buildHighscoreValue($player->getId(), HighscoreTypeEnum::research, $hsRow),
            ],
            ProfileTagEnum::MilitaryHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.score_military'),
                'value' => $this->buildHighscoreValue($player->getId(), HighscoreTypeEnum::military, $hsRow),
            ],
            ProfileTagEnum::MilitaryHighscoreBuildDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.military_built'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::MilitaryHighscoreDestroyedDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.military_destroyed'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::MilitaryHighscoreLostDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.military_lost'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::HonorHighscoreDisplay => [
                'type' => 'simple',
                'label' => __('t_ingame.profile.honor_points'),
                'value' => (int) ($player->getUser()->honor_points ?? 0),
            ],
            // Tag classifica alleanza
            ProfileTagEnum::AllianceHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceHighscoreDisplay'),
                'value' => $this->buildAllianceHighscoreValue($player, 'general'),
            ],
            ProfileTagEnum::AllianceEconomyHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceEconomyHighscoreDisplay'),
                'value' => $this->buildAllianceHighscoreValue($player, 'economy'),
            ],
            ProfileTagEnum::AllianceResearchHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceResearchHighscoreDisplay'),
                'value' => $this->buildAllianceHighscoreValue($player, 'research'),
            ],
            ProfileTagEnum::AllianceMilitaryHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceMilitaryHighscoreDisplay'),
                'value' => $this->buildAllianceHighscoreValue($player, 'military'),
            ],
            ProfileTagEnum::AllianceMilitaryBuildHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceMilitaryBuildHighscoreDisplay'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::AllianceMilitaryDestroyedHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceMilitaryDestroyedHighscoreDisplay'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::AllianceMilitaryLostHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceMilitaryLostHighscoreDisplay'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
            ProfileTagEnum::AllianceHonorHighscoreDisplay => [
                'type' => 'highscore',
                'label' => __('t_ingame.profile.tag_allianceHonorHighscoreDisplay'),
                'value' => ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0],
            ],
        };
    }

    /**
     * @return array{tag:string,name:string,id:int}|null
     */
    private function buildAllianceValue(PlayerService $player): array|null
    {
        $a = $player->getUser()->alliance ?? null;
        if (!$a) {
            return null;
        }
        /** @var Alliance $a */
        return ['tag' => (string) $a->alliance_tag, 'name' => (string) $a->alliance_name, 'id' => (int) $a->id];
    }

    /**
     * @return array{name:string|null,machine:string}
     */
    private function buildAllianceClassValue(PlayerService $player): array
    {
        $a = $player->getUser()->alliance ?? null;
        /** @var Alliance|null $a */
        $cls = $a !== null ? $a->allianceClass() : null;
        return [
            'name' => $cls !== null ? $cls->getName() : null,
            'machine' => $cls !== null ? $cls->getMachineName() : 'neutral',
        ];
    }

    /**
     * @return array{rank:int, points:int, change:string, delta:int}
     */
    private function buildAllianceHighscoreValue(PlayerService $player, string $key): array
    {
        $a = $player->getUser()->alliance ?? null;
        if (!$a) {
            return ['rank' => 0, 'points' => 0, 'change' => 'point', 'delta' => 0];
        }
        /** @var Alliance $a */
        $hs = AllianceHighscore::where('alliance_id', $a->id)->first();
        return [
            'rank' => $hs !== null ? (int) ($hs->{$key.'_rank'} ?? 0) : 0,
            'points' => $hs !== null ? (int) ($hs->{$key} ?? 0) : 0,
            'change' => 'point',
            'delta' => 0,
        ];
    }

    private function getShipsTotal(PlayerService $player): int
    {
        return $this->highscoreService->getPlayerTotalShipCount($player);
    }

    private function getExpeditionAmount(PlayerService $player): int
    {
        // Conta missioni Expedition (mission_type=15) processate da questo giocatore.
        try {
            return (int) FleetMission::where('user_id', $player->getId())
                ->where('mission_type', 15)
                ->where('processed', true)
                ->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getHighscoreRow(int $playerId): Highscore|null
    {
        return Highscore::where('player_id', $playerId)->first();
    }

    /**
     * @return array{rank:int, points:int, change:string, delta:int}
     */
    private function buildHighscoreValue(int $playerId, HighscoreTypeEnum $type, Highscore|null $hs): array
    {
        $rank = $hs !== null ? (int) ($hs->{$type->name.'_rank'} ?? 0) : 0;
        $points = $hs !== null ? (int) ($hs->{$type->name} ?? 0) : 0;
        $delta = $this->rankHistoryService->getRankDelta($playerId, $type, $rank);

        return [
            'rank' => $rank,
            'points' => $points,
            'change' => $delta['change'],
            'delta' => $delta['delta'],
        ];
    }
}

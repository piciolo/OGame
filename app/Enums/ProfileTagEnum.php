<?php

namespace OGame\Enums;

/**
 * Tag visualizzabili nel profilo OGame. Replica fedele dei `data-type` Gameforge
 * (camelCase + suffisso `Display`). Ogni tag dichiara la sezione di destinazione
 * (`profile` per .profilePageInfo, `moreInfo` per .moreInfoHolder) e se è removibile
 * dall'utente in edit mode (es. PlayerTitle è fisso).
 *
 * Le voci legate alle Forme di Vita (lifeform*) sono volutamente escluse perché
 * il sistema FdV non è implementato in OGameX.
 */
enum ProfileTagEnum: string
{
    // ── Sezione PROFILE (.profilePageInfo) ───────────────────────────
    case PlayerTitle = 'playerTitle';                      // sempre presente, non removibile
    case HonorDisplay = 'honorDisplay';
    case AllianceDisplay = 'allianceDisplay';
    case CharacterClassDisplay = 'characterClassDisplay';
    case TotalHighscoreDisplay = 'totalHighscoreDisplay';
    case AchievementPointsDisplay = 'achievementPointsDisplay';
    case ShipsOwnedDisplay = 'shipsOwnedDisplay';
    case LanguageDisplay = 'languageDisplay';
    case CompletedAchievementsDisplay = 'completedAchievementsDisplay';
    case ExpeditionAmountDisplay = 'expeditionAmountDisplay';
    case AllianceClassDisplay = 'allianceClassDisplay';

    // ── Sezione MOREINFO (.moreInfoHolder) ───────────────────────────
    case EconomyHighscoreDisplay = 'economyHighscoreDisplay';
    case ResearchHighscoreDisplay = 'researchHighscoreDisplay';
    case MilitaryHighscoreDisplay = 'militaryHighscoreDisplay';
    case MilitaryHighscoreBuildDisplay = 'militaryHighscoreBuildDisplay';
    case MilitaryHighscoreDestroyedDisplay = 'militaryHighscoreDestroyedDisplay';
    case MilitaryHighscoreLostDisplay = 'militaryHighscoreLostDisplay';
    case HonorHighscoreDisplay = 'honorHighscoreDisplay';
    case AllianceHighscoreDisplay = 'allianceHighscoreDisplay';
    case AllianceEconomyHighscoreDisplay = 'allianceEconomyHighscoreDisplay';
    case AllianceResearchHighscoreDisplay = 'allianceResearchHighscoreDisplay';
    case AllianceMilitaryHighscoreDisplay = 'allianceMilitaryHighscoreDisplay';
    case AllianceMilitaryBuildHighscoreDisplay = 'allianceMilitaryBuildHighscoreDisplay';
    case AllianceMilitaryDestroyedHighscoreDisplay = 'allianceMilitaryDestroyedHighscoreDisplay';
    case AllianceMilitaryLostHighscoreDisplay = 'allianceMilitaryLostHighscoreDisplay';
    case AllianceHonorHighscoreDisplay = 'allianceHonorHighscoreDisplay';

    /** @return 'profile'|'moreInfo' */
    public function section(): string
    {
        return match ($this) {
            self::PlayerTitle,
            self::HonorDisplay,
            self::AllianceDisplay,
            self::CharacterClassDisplay,
            self::TotalHighscoreDisplay,
            self::AchievementPointsDisplay,
            self::ShipsOwnedDisplay,
            self::LanguageDisplay,
            self::CompletedAchievementsDisplay,
            self::ExpeditionAmountDisplay,
            self::AllianceClassDisplay => 'profile',
            default => 'moreInfo',
        };
    }

    public function removable(): bool
    {
        return $this !== self::PlayerTitle;
    }

    public function langKey(): string
    {
        return 't_ingame.profile.tag_'.$this->value;
    }

    /**
     * @return array<int, ProfileTagEnum>
     */
    public static function profileDefaults(): array
    {
        return [
            self::PlayerTitle,
            self::HonorDisplay,
            self::AllianceDisplay,
            self::CharacterClassDisplay,
            self::TotalHighscoreDisplay,
            self::AchievementPointsDisplay,
        ];
    }

    /**
     * @return array<int, ProfileTagEnum>
     */
    public static function moreInfoDefaults(): array
    {
        return [
            self::EconomyHighscoreDisplay,
            self::ResearchHighscoreDisplay,
            self::MilitaryHighscoreDisplay,
            self::MilitaryHighscoreBuildDisplay,
            self::MilitaryHighscoreDestroyedDisplay,
            self::MilitaryHighscoreLostDisplay,
            self::HonorHighscoreDisplay,
        ];
    }

    /**
     * @return array<int, ProfileTagEnum>
     */
    public static function casesForSection(string $section): array
    {
        return array_values(array_filter(self::cases(), fn(self $t) => $t->section() === $section));
    }
}

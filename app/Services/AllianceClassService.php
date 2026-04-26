<?php

namespace OGame\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use OGame\Enums\AllianceClass;
use OGame\Enums\DarkMatterTransactionType;
use OGame\Models\Alliance;
use OGame\Models\AllianceMember;
use OGame\Models\Planet;
use OGame\Models\PlanetBoost;
use OGame\Models\User;

/**
 * AllianceClassService — primary service for Alliance Class operations.
 *
 * Mirrors CharacterClassService but applies the class to an Alliance entity,
 * meaning every member of the alliance benefits from the active class.
 *
 * Activation rules (verified on official OGame UI s274-it.ogame.gameforge.com):
 *   - Cost per change: 500.000 MO (paid by the activator, debited from their account)
 *   - First activation FREE, but only after 14 days from alliance creation
 *   - Activator must hold PERMISSION_MANAGE_CLASSES (or be the founder)
 *   - Cooldown: 5 minutes between class changes (from board sources)
 */
class AllianceClassService
{
    private const CHANGE_COOLDOWN_SECONDS = 300; // 5 minutes

    public function __construct(
        private readonly DarkMatterService $darkMatterService,
    ) {
    }

    public function getAllianceClass(Alliance $alliance): ?AllianceClass
    {
        return $alliance->allianceClass();
    }

    /**
     * Returns true if the alliance currently has any class set.
     */
    public function hasClass(Alliance $alliance): bool
    {
        return $alliance->alliance_class !== null;
    }

    /**
     * Returns true if the user can manage alliance classes for the given alliance.
     * Founders always can; other members need PERMISSION_MANAGE_CLASSES on their rank.
     */
    public function userCanManage(Alliance $alliance, User $user): bool
    {
        $member = AllianceMember::where('alliance_id', $alliance->id)
            ->where('user_id', $user->id)
            ->first();
        if ($member === null) {
            return false;
        }
        return $member->hasPermission(\OGame\Models\AllianceRank::PERMISSION_MANAGE_CLASSES);
    }

    /**
     * Cost in Dark Matter for the next class change. Returns 0 when the
     * first-time-free activation is available.
     */
    public function getChangeCost(Alliance $alliance): int
    {
        if ($this->isFreeActivationAvailable($alliance)) {
            return 0;
        }
        return AllianceClass::WARRIOR->getChangeCost();
    }

    /**
     * Free activation requires:
     *   - the alliance has never used the free slot
     *   - the alliance is at least N days old (verified 14 days on OGame ufficiale)
     */
    public function isFreeActivationAvailable(Alliance $alliance): bool
    {
        if ($alliance->alliance_class_free_used) {
            return false;
        }
        $delayDays = AllianceClass::getFreeActivationDelayDays();
        if ($alliance->created_at === null) {
            return false;
        }
        return $alliance->created_at->copy()->addDays($delayDays)->isPast();
    }

    /**
     * Timestamp at which the free activation becomes available.
     */
    public function getFreeActivationAvailableAt(Alliance $alliance): ?\Illuminate\Support\Carbon
    {
        if ($alliance->created_at === null) {
            return null;
        }
        return $alliance->created_at->copy()->addDays(AllianceClass::getFreeActivationDelayDays());
    }

    /**
     * Activate / change the alliance class. Atomic: locks the alliance row,
     * validates cooldown + cost, debits DM if needed, persists state.
     *
     * @throws Exception
     */
    public function selectClass(Alliance $alliance, User $actor, AllianceClass $newClass): void
    {
        DB::transaction(function () use ($alliance, $actor, $newClass) {
            /** @var Alliance $locked */
            $locked = Alliance::query()->whereKey($alliance->id)->lockForUpdate()->firstOrFail();

            if (!$this->userCanManage($locked, $actor)) {
                throw new Exception('alliance_class_no_permission');
            }
            if ($locked->alliance_class === $newClass->value) {
                throw new Exception('alliance_class_already_selected');
            }
            if ($locked->alliance_class_changed_at !== null
                && $locked->alliance_class_changed_at->copy()
                    ->addSeconds(self::CHANGE_COOLDOWN_SECONDS)->isFuture()) {
                throw new Exception('alliance_class_cooldown');
            }

            $cost = $this->getChangeCost($locked);
            if ($cost > 0) {
                if (!$this->darkMatterService->canAfford($actor, $cost)) {
                    throw new Exception('alliance_class_insufficient_dm');
                }
                $this->darkMatterService->debit(
                    $actor,
                    $cost,
                    DarkMatterTransactionType::ALLIANCE_CLASS->value,
                    'Alliance class change to ' . $newClass->getName()
                );
            }

            $locked->alliance_class = $newClass->value;
            $locked->alliance_class_changed_at = now();
            $locked->alliance_class_free_used = true;
            $locked->save();
        });
    }

    // -----------------------------------------------------------------
    //  Bonus accessors — read by PlayerService / PlanetService / etc.
    //  All take a User: the user's alliance is resolved internally.
    // -----------------------------------------------------------------

    private function classFor(User $user): ?AllianceClass
    {
        if ($user->alliance_id === null) {
            return null;
        }
        $alliance = Alliance::find($user->alliance_id);
        if ($alliance === null) {
            return null;
        }
        return $alliance->allianceClass();
    }

    public function isWarrior(User $user): bool
    {
        return $this->classFor($user) === AllianceClass::WARRIOR;
    }

    public function isTrader(User $user): bool
    {
        return $this->classFor($user) === AllianceClass::TRADER;
    }

    public function isResearcher(User $user): bool
    {
        return $this->classFor($user) === AllianceClass::RESEARCHER;
    }

    /** Mercante: +5% produzione miniere. */
    public function getMineProductionBonus(User $user): float
    {
        return $this->isTrader($user) ? 1.05 : 1.0;
    }

    /** Mercante: +5% produzione di energia. */
    public function getEnergyProductionBonus(User $user): float
    {
        return $this->isTrader($user) ? 1.05 : 1.0;
    }

    /** Mercante: +10% capienza deposito (pianeti & lune). */
    public function getStorageBonus(User $user): float
    {
        return $this->isTrader($user) ? 1.10 : 1.0;
    }

    /** Mercante: +10% velocità Cargo. */
    public function getCargoSpeedBonus(User $user): float
    {
        return $this->isTrader($user) ? 1.10 : 1.0;
    }

    /** Guerriero: +1 livello ricerche militari (armi/scudi/armatura). */
    public function getAdditionalCombatResearchLevels(User $user): int
    {
        return $this->isWarrior($user) ? 1 : 0;
    }

    /** Guerriero: +1 livello ricerca spionaggio. */
    public function getAdditionalEspionageResearchLevels(User $user): int
    {
        return $this->isWarrior($user) ? 1 : 0;
    }

    /** Guerriero: +10% velocità navi per voli tra membri alleanza (ACS). */
    public function getAllianceFlightSpeedBonus(User $user): float
    {
        return $this->isWarrior($user) ? 1.10 : 1.0;
    }

    /** Ricercatore: +5% pianeti più grandi in colonizzazione. */
    public function getPlanetSizeBonus(User $user): float
    {
        return $this->isResearcher($user) ? 1.05 : 1.0;
    }

    /** Ricercatore: +10% velocità voli verso destinazione spedizione. */
    public function getExpeditionSpeedBonus(User $user): float
    {
        return $this->isResearcher($user) ? 1.10 : 1.0;
    }
}

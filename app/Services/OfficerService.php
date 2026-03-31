<?php

namespace OGame\Services;

use Exception;
use OGame\Enums\DarkMatterTransactionType;
use OGame\Models\Officer;
use OGame\Models\User;

/**
 * Class OfficerService.
 *
 * Handles officer activation, cost calculation, and bonus lookups.
 *
 * @package OGame\Services
 */
class OfficerService
{
    /**
     * Officer type ID to key mapping (matching original OGame type IDs).
     */
    public const TYPE_MAP = [
        2 => 'commander',
        3 => 'admiral',
        4 => 'engineer',
        5 => 'geologist',
        6 => 'technocrat',
        12 => 'all_officers',
    ];

    /**
     * Costs in Dark Matter per officer per duration (days).
     */
    public const COSTS = [
        'commander'    => [7 => 10000, 30 => 30000, 90 => 75000],
        'admiral'      => [7 => 10000, 30 => 30000, 90 => 75000],
        'engineer'     => [7 => 10000, 30 => 30000, 90 => 75000],
        'geologist'    => [7 => 10000, 30 => 30000, 90 => 75000],
        'technocrat'   => [7 => 10000, 30 => 30000, 90 => 75000],
        'all_officers' => [7 => 40000, 30 => 120000, 90 => 300000],
    ];

    /**
     * Valid durations in days.
     */
    public const DURATIONS = [7, 30, 90];

    /**
     * In-memory cache of Officer records to avoid repeated DB queries per request.
     *
     * @var array<int, Officer>
     */
    private array $cache = [];

    public function __construct(
        private DarkMatterService $darkMatterService
    ) {
    }

    /**
     * Get or create the Officer record for a user (cached per request).
     */
    public function getOfficer(User $user): Officer
    {
        if (!isset($this->cache[$user->id])) {
            $this->cache[$user->id] = Officer::firstOrCreate(['user_id' => $user->id]);
        }
        return $this->cache[$user->id];
    }

    /**
     * Clear the in-memory cache for a user (call after purchase to refresh).
     */
    public function clearCache(User $user): void
    {
        unset($this->cache[$user->id]);
    }

    /**
     * Get officer key from type ID.
     */
    public function getKeyFromTypeId(int $typeId): ?string
    {
        return self::TYPE_MAP[$typeId] ?? null;
    }

    /**
     * Get the cost for an officer + duration combination.
     */
    public function getCost(string $officerKey, int $days): int
    {
        return self::COSTS[$officerKey][$days] ?? 0;
    }

    /**
     * Purchase/activate an officer for a user.
     *
     * @throws Exception
     */
    public function purchase(User $user, string $officerKey, int $days): void
    {
        if (!isset(self::COSTS[$officerKey])) {
            throw new Exception("Invalid officer type: {$officerKey}");
        }

        if (!in_array($days, self::DURATIONS, true)) {
            throw new Exception("Invalid duration: {$days}");
        }

        $cost = $this->getCost($officerKey, $days);

        // Debit dark matter (throws if insufficient)
        $this->darkMatterService->debit(
            $user,
            $cost,
            DarkMatterTransactionType::OFFICER_PURCHASE->value,
            "Officer activation: {$officerKey} for {$days} days"
        );

        // Activate/extend the officer
        $officer = $this->getOfficer($user);
        $officer->activate($officerKey, $days);
        $officer->save();

        // Clear cache so subsequent reads reflect the update
        $this->clearCache($user);
    }

    /**
     * Check if a specific officer is active for a user (including all_officers effect).
     */
    public function isActive(User $user, string $officerKey): bool
    {
        $officer = $this->getOfficer($user);
        return $officer->isOfficerActive($officerKey);
    }

    /**
     * Get the geologist mine production bonus multiplier.
     * Returns 1.1 if geologist active, 1.0 otherwise.
     */
    public function getMineProductionBonus(User $user): float
    {
        return $this->isActive($user, 'geologist') ? 1.10 : 1.0;
    }

    /**
     * Get the engineer energy production bonus multiplier.
     * Returns 1.1 if engineer active, 1.0 otherwise.
     */
    public function getEnergyProductionBonus(User $user): float
    {
        return $this->isActive($user, 'engineer') ? 1.10 : 1.0;
    }

    /**
     * Get additional fleet slots from commander.
     */
    public function getAdditionalFleetSlots(User $user): int
    {
        return $this->isActive($user, 'commander') ? 1 : 0;
    }

    /**
     * Get additional expedition slots from admiral.
     */
    public function getAdditionalExpeditionSlots(User $user): int
    {
        return $this->isActive($user, 'admiral') ? 1 : 0;
    }

    /**
     * Get additional fleet slots from admiral.
     */
    public function getAdmiralFleetSlots(User $user): int
    {
        return $this->isActive($user, 'admiral') ? 2 : 0;
    }

    /**
     * Get the technocrat research time multiplier.
     * Returns 0.75 if technocrat active, 1.0 otherwise.
     */
    public function getResearchTimeMultiplier(User $user): float
    {
        return $this->isActive($user, 'technocrat') ? 0.75 : 1.0;
    }

    /**
     * Get additional espionage levels from technocrat.
     */
    public function getAdditionalEspionageLevels(User $user): int
    {
        return $this->isActive($user, 'technocrat') ? 2 : 0;
    }

    /**
     * Get the all-officers commanding staff bonus for mine production.
     * +2% if all 5 officers are active (via all_officers or individually).
     */
    public function getCommandingStaffMineBonus(User $user): float
    {
        $officer = $this->getOfficer($user);
        return ($officer->getActiveOfficerCount() >= 5) ? 1.02 : 1.0;
    }

    /**
     * Get the all-officers commanding staff bonus for energy.
     * +2% if all 5 officers are active.
     */
    public function getCommandingStaffEnergyBonus(User $user): float
    {
        $officer = $this->getOfficer($user);
        return ($officer->getActiveOfficerCount() >= 5) ? 1.02 : 1.0;
    }

    /**
     * Get the all-officers commanding staff additional fleet slots.
     * +1 if all 5 officers are active.
     */
    public function getCommandingStaffFleetSlots(User $user): int
    {
        $officer = $this->getOfficer($user);
        return ($officer->getActiveOfficerCount() >= 5) ? 1 : 0;
    }

    /**
     * Get the all-officers commanding staff additional espionage levels.
     * +1 if all 5 officers are active.
     */
    public function getCommandingStaffEspionageLevels(User $user): int
    {
        $officer = $this->getOfficer($user);
        return ($officer->getActiveOfficerCount() >= 5) ? 1 : 0;
    }
}

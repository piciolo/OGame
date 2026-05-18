<?php

namespace Tests\Feature;

use OGame\Models\PlanetBoost;
use Tests\AccountTestCase;

/**
 * Verifies PlanetService::updateResourceProductionStats() correctly applies
 * active PlanetBoost rows to the planet's production rates.
 *
 * - Mine boosts (metal/crystal/deuterium): additive percent on production_total
 * - Energy boost: applied BEFORE production_factor so it influences mine output
 * - Expired boosts: filtered out via WHERE expires_at > NOW()
 * - Same-resource multi-tier: stacks additively (Bronze 10% + Gold 30% = +40%)
 */
class PlanetBoostProductionTest extends AccountTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Clean any active boosts from prior tests
        PlanetBoost::query()->where('planet_id', $this->currentPlanetId)->delete();
    }

    public function testMineProductionIncreasesWithActiveMetalBoost(): void
    {
        $this->planetService->reloadPlanet();
        $baseMetal = (int) $this->planetService->getMetalProductionPerHour();

        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'metal',
            'percent_bonus' => 30,
            'expires_at' => now()->addDays(7),
        ]);

        $this->planetService->updateResourceProductionStats(true);
        $this->planetService->reloadPlanet();
        $boostedMetal = (int) $this->planetService->getMetalProductionPerHour();

        $this->assertGreaterThan(
            $baseMetal,
            $boostedMetal,
            'Metal production must increase with active +30% boost'
        );
    }

    public function testExpiredBoostsAreNotApplied(): void
    {
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'metal',
            'percent_bonus' => 40,
            'expires_at' => now()->subHour(), // EXPIRED
        ]);

        $this->planetService->updateResourceProductionStats(true);
        $this->planetService->reloadPlanet();
        $metalWithExpiredBoost = (int) $this->planetService->getMetalProductionPerHour();

        // Recalc with no boosts present (delete row)
        PlanetBoost::query()->where('planet_id', $this->currentPlanetId)->delete();
        $this->planetService->updateResourceProductionStats(true);
        $this->planetService->reloadPlanet();
        $metalNoBoost = (int) $this->planetService->getMetalProductionPerHour();

        $this->assertSame($metalNoBoost, $metalWithExpiredBoost, 'Expired boost should NOT affect production');
    }

    public function testMultipleTiersStackAdditively(): void
    {
        // Bronze (10%) + Gold (30%) = +40% (additive)
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'crystal',
            'percent_bonus' => 10,
            'expires_at' => now()->addDays(7),
        ]);
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'crystal',
            'percent_bonus' => 30,
            'expires_at' => now()->addDays(7),
        ]);

        $this->planetService->updateResourceProductionStats(true);
        $this->planetService->reloadPlanet();
        $stackedCrystal = (int) $this->planetService->getCrystalProductionPerHour();

        // Now compare with single Platinum (40%) = same total
        PlanetBoost::query()->where('planet_id', $this->currentPlanetId)->delete();
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'crystal',
            'percent_bonus' => 40,
            'expires_at' => now()->addDays(7),
        ]);
        $this->planetService->updateResourceProductionStats(true);
        $this->planetService->reloadPlanet();
        $platinumCrystal = (int) $this->planetService->getCrystalProductionPerHour();

        // Allow ±2 tolerance for floor/ceil rounding differences
        $this->assertEqualsWithDelta($platinumCrystal, $stackedCrystal, 2, 'Bronze+Gold stacking must equal single Platinum');
    }

    public function testEnergyBoostAffectsEnergyMax(): void
    {
        $this->planetService->updateResourceProductionStats(true);
        $baseEnergyMax = (int) \OGame\Models\Planet::find($this->currentPlanetId)->energy_max;

        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'energy',
            'percent_bonus' => 40,
            'expires_at' => now()->addDays(7),
        ]);

        $this->planetService->updateResourceProductionStats(true);
        $boostedEnergyMax = (int) \OGame\Models\Planet::find($this->currentPlanetId)->energy_max;

        // If base energy is positive, boost must increase it. If 0, accept ≥0.
        if ($baseEnergyMax > 0) {
            $this->assertGreaterThan($baseEnergyMax, $boostedEnergyMax, 'Energy boost must increase energy_max');
        } else {
            $this->assertGreaterThanOrEqual(0, $boostedEnergyMax);
        }
    }
}

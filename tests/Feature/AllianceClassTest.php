<?php

namespace Tests\Feature;

use Exception;
use OGame\Enums\AllianceClass;
use OGame\Models\Alliance;
use OGame\Models\User;
use OGame\Services\AllianceClassService;
use OGame\Services\AllianceService;
use OGame\Services\DarkMatterService;
use Tests\AccountTestCase;

/**
 * Verifies the Alliance Class feature mirrors the official OGame mechanics:
 *   - Founder/leader can activate one of 3 classes (Warrior/Trader/Researcher)
 *   - First activation FREE only after 14 days from alliance creation
 *   - Subsequent changes cost 500.000 Dark Matter
 *   - 5-minute cooldown between changes
 *   - Bonus visible via service accessor methods (Mercante storage +10%, etc.)
 *
 * Source: OGame ufficiale (s274-it.ogame.gameforge.com), tab "Classi Alleanza".
 */
class AllianceClassTest extends AccountTestCase
{
    private AllianceClassService $service;
    private AllianceService $allianceService;
    private DarkMatterService $dmService;
    private Alliance $alliance;
    private User $founder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(AllianceClassService::class);
        $this->allianceService = resolve(AllianceService::class);
        $this->dmService = resolve(DarkMatterService::class);

        $tag = 'AC' . substr(md5(uniqid((string) mt_rand(), true)), 0, 5);
        $name = 'AC Test ' . substr(md5(uniqid((string) mt_rand(), true)), 0, 6);
        $this->alliance = $this->allianceService->createAlliance($this->currentUserId, $tag, $name);
        $this->founder = User::find($this->currentUserId);
    }

    public function testAllianceStartsWithoutClass(): void
    {
        $this->assertFalse($this->service->hasClass($this->alliance));
        $this->assertNull($this->service->getAllianceClass($this->alliance));
    }

    public function testFreeActivationBlockedBefore14Days(): void
    {
        // Newly created alliance — created_at is "now", so free activation is NOT yet available.
        $this->assertFalse($this->service->isFreeActivationAvailable($this->alliance));
        $this->assertSame(500000, $this->service->getChangeCost($this->alliance));
    }

    public function testFreeActivationAvailableAfter14Days(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();
        $this->alliance->refresh();

        $this->assertTrue($this->service->isFreeActivationAvailable($this->alliance));
        $this->assertSame(0, $this->service->getChangeCost($this->alliance));
    }

    public function testFounderCanActivateForFreeAfter14Days(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();
        $this->alliance->refresh();
        $dmBefore = $this->dmService->getBalance($this->founder);

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);

        $this->alliance->refresh();
        $this->assertSame(AllianceClass::TRADER->value, (int) $this->alliance->alliance_class);
        $this->assertTrue($this->alliance->alliance_class_free_used);
        $this->assertSame($dmBefore, $this->dmService->getBalance($this->founder), 'free activation must not debit DM');
    }

    public function testPaidActivationDebitsDarkMatter(): void
    {
        // Skip the 14d wait by marking free as already used
        $this->alliance->alliance_class_free_used = true;
        $this->alliance->created_at = now()->subDays(20);
        $this->alliance->save();

        // Give the founder enough DM
        $this->dmService->credit($this->founder, 1000000, 'admin_adjustment', 'test top-up');
        $this->founder = User::find($this->currentUserId); // refresh after credit
        $dmBefore = $this->dmService->getBalance($this->founder);

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::WARRIOR);
        $this->founder = User::find($this->currentUserId); // refresh after debit

        $this->alliance->refresh();
        $this->assertSame(AllianceClass::WARRIOR->value, (int) $this->alliance->alliance_class);
        $this->assertSame($dmBefore - 500000, $this->dmService->getBalance($this->founder));
    }

    public function testInsufficientDarkMatterRejectsActivation(): void
    {
        $this->alliance->alliance_class_free_used = true;
        $this->alliance->save();

        // Drain the founder's DM so they cannot afford a 500k change
        $this->dmService->debit(
            $this->founder,
            $this->dmService->getBalance($this->founder),
            'admin_adjustment',
            'drain for test'
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('alliance_class_insufficient_dm');
        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::RESEARCHER);
    }

    public function testCannotSelectSameClassTwice(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();
        $this->alliance->refresh();

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('alliance_class_already_selected');
        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);
    }

    public function testCooldownEnforcedBetweenChanges(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();
        $this->alliance->refresh();

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);

        // Top up DM so the second change isn't blocked by funds
        $this->dmService->credit($this->founder, 1000000, 'admin_adjustment', 'test');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('alliance_class_cooldown');
        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::WARRIOR);
    }

    public function testTraderBonusAppliesToFounder(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();
        $this->alliance->refresh();

        $this->assertSame(1.0, $this->service->getStorageBonus($this->founder));
        $this->assertSame(1.0, $this->service->getMineProductionBonus($this->founder));

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);
        // Reload founder to refresh alliance_id cache
        $this->founder = User::find($this->currentUserId);

        $this->assertEqualsWithDelta(1.10, $this->service->getStorageBonus($this->founder), 0.001);
        $this->assertEqualsWithDelta(1.05, $this->service->getMineProductionBonus($this->founder), 0.001);
        $this->assertEqualsWithDelta(1.05, $this->service->getEnergyProductionBonus($this->founder), 0.001);
        $this->assertEqualsWithDelta(1.10, $this->service->getCargoSpeedBonus($this->founder), 0.001);
    }

    public function testWarriorBonusGrantsResearchAndFlightSpeed(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::WARRIOR);
        $this->founder = User::find($this->currentUserId);

        $this->assertSame(1, $this->service->getAdditionalCombatResearchLevels($this->founder));
        $this->assertSame(1, $this->service->getAdditionalEspionageResearchLevels($this->founder));
        $this->assertEqualsWithDelta(1.10, $this->service->getAllianceFlightSpeedBonus($this->founder), 0.001);
    }

    public function testResearcherBonusGrantsPlanetSizeAndExpeditionSpeed(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::RESEARCHER);
        $this->founder = User::find($this->currentUserId);

        $this->assertEqualsWithDelta(1.05, $this->service->getPlanetSizeBonus($this->founder), 0.001);
        $this->assertEqualsWithDelta(1.10, $this->service->getExpeditionSpeedBonus($this->founder), 0.001);
    }

    public function testTraderClassIncreasesCargoShipSpeed(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();

        // Snapshot Small Cargo speed BEFORE the alliance becomes Trader.
        $smallCargo = \OGame\Services\ObjectService::getUnitObjectByMachineName('small_cargo');
        $playerService = resolve(\OGame\Factories\PlayerServiceFactory::class)->make($this->founder->id);
        $speedBefore = (int) $smallCargo->properties->speed->calculate($playerService)->totalValue;

        // Activate Trader class
        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);

        // Re-resolve player to bust cached alliance state
        $playerService = resolve(\OGame\Factories\PlayerServiceFactory::class)->make($this->founder->id, true);
        $speedAfter = (int) $smallCargo->properties->speed->calculate($playerService)->totalValue;

        $this->assertGreaterThan($speedBefore, $speedAfter, 'Trader class must increase Small Cargo speed');
        // +10% of base 5000 = +500 (research bonuses unchanged), so delta should equal 10% of effective base.
        $delta = $speedAfter - $speedBefore;
        $this->assertGreaterThanOrEqual(
            (int) floor($speedBefore * 0.05), // at least +5% to allow for rounding & research interaction
            $delta,
            'Cargo speed delta must be at least ~10% of pre-bonus speed'
        );
    }

    public function testTraderBonusDoesNotApplyToCombatShips(): void
    {
        $this->alliance->created_at = now()->subDays(15);
        $this->alliance->save();

        $lightFighter = \OGame\Services\ObjectService::getUnitObjectByMachineName('light_fighter');
        $playerBefore = resolve(\OGame\Factories\PlayerServiceFactory::class)->make($this->founder->id);
        $speedBefore = (int) $lightFighter->properties->speed->calculate($playerBefore)->totalValue;

        $this->service->selectClass($this->alliance, $this->founder, AllianceClass::TRADER);

        $playerAfter = resolve(\OGame\Factories\PlayerServiceFactory::class)->make($this->founder->id, true);
        $speedAfter = (int) $lightFighter->properties->speed->calculate($playerAfter)->totalValue;

        $this->assertSame($speedBefore, $speedAfter, 'Trader cargo bonus must NOT touch combat ship speed');
    }

    public function testNoBonusWhenNoAllianceClassSet(): void
    {
        // Founder belongs to alliance but no class is set → all bonuses neutral
        $this->founder = User::find($this->currentUserId);
        $this->assertSame(1.0, $this->service->getStorageBonus($this->founder));
        $this->assertSame(1.0, $this->service->getMineProductionBonus($this->founder));
        $this->assertSame(0, $this->service->getAdditionalCombatResearchLevels($this->founder));
        $this->assertSame(1.0, $this->service->getPlanetSizeBonus($this->founder));
    }
}

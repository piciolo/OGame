<?php

namespace Tests\Feature;

use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\NPCFleetGeneratorService;
use OGame\Services\NPCPlayerService;
use OGame\Services\ObjectService;
use Tests\AccountTestCase;

/**
 * Tests for NPCFleetGeneratorService — drives the random expedition NPC fleet logic.
 *
 * The service has random elements (battle tier, fleet variance, RNG bonus picks),
 * so tests focus on the *deterministic* invariants of the public API:
 *
 *  - generateEnemyFleet returns the documented array shape {fleet, player}
 *  - the returned NPCPlayer has the right id/username for the requested type
 *  - pirate tech = max(0, player_tech - 3) for weapon/shield/armor
 *  - alien  tech = player_tech + 3 for weapon/shield/armor
 *  - empty player fleet still returns a valid UnitCollection (no crash)
 *  - generated NPC fleet always contains at least one unit (bonus ships are
 *    appended unconditionally based on tier)
 *  - aliens never receive negative tech (only relevant if base tech is 0)
 */
class NPCFleetGeneratorServiceTest extends AccountTestCase
{
    private NPCFleetGeneratorService $service;
    private ObjectService $objects;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(NPCFleetGeneratorService::class);
        $this->objects = resolve(ObjectService::class);

        // Set baseline tech so we can predict pirate/alien tech levels.
        $this->playerSetResearchLevel('weapon_technology', 5);
        $this->playerSetResearchLevel('shielding_technology', 4);
        $this->playerSetResearchLevel('armor_technology', 3);
    }

    private function playerFleetWithLightFighters(int $count = 100): UnitCollection
    {
        $fleet = new UnitCollection();
        $fleet->addUnit($this->objects->getShipObjectByMachineName('light_fighter'), $count);
        return $fleet;
    }

    public function testGenerateEnemyFleetReturnsDocumentedShape(): void
    {
        $playerFleet = $this->playerFleetWithLightFighters(50);
        $player = $this->planetService->getPlayer();

        $result = $this->service->generateEnemyFleet($playerFleet, $player, 'pirate');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('fleet', $result);
        $this->assertArrayHasKey('player', $result);
        $this->assertInstanceOf(UnitCollection::class, $result['fleet']);
        $this->assertInstanceOf(NPCPlayerService::class, $result['player']);
    }

    public function testPirateTechIsPlayerTechMinusThreeFloorAtZero(): void
    {
        // Player has tech 5/4/3 so pirate should be 2/1/0.
        $playerFleet = $this->playerFleetWithLightFighters(50);
        $player = $this->planetService->getPlayer();

        $result = $this->service->generateEnemyFleet($playerFleet, $player, 'pirate');
        $pirate = $result['player'];

        $this->assertSame('pirate', $pirate->getNpcType());
        $this->assertSame(-1, $pirate->getId());
        $this->assertSame(2, $pirate->getResearchLevel('weapon_technology'));
        $this->assertSame(1, $pirate->getResearchLevel('shielding_technology'));
        $this->assertSame(0, $pirate->getResearchLevel('armor_technology'));
    }

    public function testPirateTechIsClampedAtZeroForLowPlayer(): void
    {
        // Reset all combat tech to 0; pirates must not go negative.
        $this->playerSetResearchLevel('weapon_technology', 0);
        $this->playerSetResearchLevel('shielding_technology', 0);
        $this->playerSetResearchLevel('armor_technology', 0);

        $playerFleet = $this->playerFleetWithLightFighters(10);
        $player = $this->planetService->getPlayer();

        $result = $this->service->generateEnemyFleet($playerFleet, $player, 'pirate');
        $pirate = $result['player'];

        $this->assertSame(0, $pirate->getResearchLevel('weapon_technology'));
        $this->assertSame(0, $pirate->getResearchLevel('shielding_technology'));
        $this->assertSame(0, $pirate->getResearchLevel('armor_technology'));
    }

    public function testAlienTechIsPlayerTechPlusThree(): void
    {
        $playerFleet = $this->playerFleetWithLightFighters(50);
        $player = $this->planetService->getPlayer();

        $result = $this->service->generateEnemyFleet($playerFleet, $player, 'alien');
        $alien = $result['player'];

        $this->assertSame('alien', $alien->getNpcType());
        $this->assertSame(-2, $alien->getId());
        $this->assertSame(8, $alien->getResearchLevel('weapon_technology'));    // 5+3
        $this->assertSame(7, $alien->getResearchLevel('shielding_technology')); // 4+3
        $this->assertSame(6, $alien->getResearchLevel('armor_technology'));     // 3+3
    }

    public function testEmptyPlayerFleetStillProducesValidNpcFleet(): void
    {
        $emptyFleet = new UnitCollection();
        $player = $this->planetService->getPlayer();

        $result = $this->service->generateEnemyFleet($emptyFleet, $player, 'pirate');
        $this->assertInstanceOf(UnitCollection::class, $result['fleet']);
        // The bonus ships (5 light_fighter / 5 heavy_fighter / etc.) are appended
        // regardless of player fleet value, so the NPC fleet should never be empty.
        $this->assertNotEmpty($result['fleet']->units);
    }

    public function testGeneratedFleetAlwaysContainsAtLeastTheBonusShips(): void
    {
        $playerFleet = $this->playerFleetWithLightFighters(10);
        $player = $this->planetService->getPlayer();

        // Run several iterations to mitigate randomness — the bonus ships are
        // appended unconditionally, so total amount across all units must be
        // at least the smallest bonus (2 destroyers in tier-3 alien battle).
        for ($i = 0; $i < 5; $i++) {
            $result = $this->service->generateEnemyFleet($playerFleet, $player, 'pirate');
            $totalAmount = 0;
            foreach ($result['fleet']->units as $unit) {
                $totalAmount += $unit->amount;
            }
            $this->assertGreaterThanOrEqual(2, $totalAmount, 'NPC fleet must always include at least the tier bonus ships.');
        }
    }

    public function testNpcTypeIsAlwaysPropagated(): void
    {
        $playerFleet = $this->playerFleetWithLightFighters(10);
        $player = $this->planetService->getPlayer();

        $pirate = $this->service->generateEnemyFleet($playerFleet, $player, 'pirate')['player'];
        $alien = $this->service->generateEnemyFleet($playerFleet, $player, 'alien')['player'];

        $this->assertSame('Pirates', $pirate->getUsername());
        $this->assertSame('Aliens', $alien->getUsername());
    }
}

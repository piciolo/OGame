<?php

namespace Tests\Feature;

use OGame\Factories\PlayerServiceFactory;
use OGame\GameObjects\Models\Units\UnitCollection;
use OGame\Services\NPCPlanetService;
use OGame\Services\NPCPlayerService;
use OGame\Services\ObjectService;
use OGame\Services\SettingsService;
use Tests\AccountTestCase;

/**
 * Verifies NPCPlanetService — the PlanetService stand-in used by expedition battles.
 *
 *  - getPlayer() returns the injected NPC player (so battle engine sees NPC tech)
 *  - getShipUnits() returns the injected NPC fleet
 *  - getDefenseUnits() always returns an empty UnitCollection (NPCs have no defenses)
 *  - getObjectAmount() returns 0 for any machine name (overrides parent's DB read,
 *    preventing the player's own defenses from leaking into the NPC defending fleet)
 */
class NPCPlanetServiceTest extends AccountTestCase
{
    public function testNpcPlanetReturnsInjectedPlayerAndFleet(): void
    {
        $factory = resolve(PlayerServiceFactory::class);
        $settings = resolve(SettingsService::class);
        $objects = resolve(ObjectService::class);

        $npcPlayer = new NPCPlayerService('alien', 7, 6, 5);
        $npcFleet = new UnitCollection();
        $npcFleet->addUnit($objects->getShipObjectByMachineName('cruiser'), 12);

        $npcPlanet = new NPCPlanetService($factory, $settings, $npcPlayer, $npcFleet, $this->currentPlanetId);

        $this->assertSame($npcPlayer, $npcPlanet->getPlayer());
        $this->assertSame($npcFleet, $npcPlanet->getShipUnits());
    }

    public function testNpcPlanetDefensesAreAlwaysEmpty(): void
    {
        $factory = resolve(PlayerServiceFactory::class);
        $settings = resolve(SettingsService::class);

        $npcPlayer = new NPCPlayerService('pirate', 1, 1, 1);
        $npcFleet = new UnitCollection();
        $npcPlanet = new NPCPlanetService($factory, $settings, $npcPlayer, $npcFleet, $this->currentPlanetId);

        $defenses = $npcPlanet->getDefenseUnits();
        $this->assertInstanceOf(UnitCollection::class, $defenses);
        $this->assertSame([], $defenses->units, 'NPC planets have NO defensive units.');
    }

    public function testGetObjectAmountAlwaysReturnsZero(): void
    {
        $factory = resolve(PlayerServiceFactory::class);
        $settings = resolve(SettingsService::class);

        $npcPlayer = new NPCPlayerService('pirate', 0, 0, 0);
        $npcFleet = new UnitCollection();
        $npcPlanet = new NPCPlanetService($factory, $settings, $npcPlayer, $npcFleet, $this->currentPlanetId);

        // Even if the underlying DB row has buildings/units, NPC must report 0.
        $this->assertSame(0, $npcPlanet->getObjectAmount('rocket_launcher'));
        $this->assertSame(0, $npcPlanet->getObjectAmount('light_fighter'));
        $this->assertSame(0, $npcPlanet->getObjectAmount('metal_mine'));
        $this->assertSame(0, $npcPlanet->getObjectAmount('any_machine_name_at_all'));
    }
}

<?php

namespace Tests\Unit;

use OGame\Services\NPCPlayerService;
use OGame\Services\PlayerService;
use Tests\TestCase;

/**
 * Unit tests for NPCPlayerService — a stand-in PlayerService used by expedition battles.
 *
 *  - getId() returns -1 for pirates and -2 for aliens (negative IDs distinguish NPCs)
 *  - getUsername() returns the documented strings
 *  - getNpcType() echoes the constructor argument
 *  - getResearchLevel() exposes weapon/shield/armor; everything else is 0
 *  - The service extends PlayerService (battle engine relies on the base contract)
 */
class NPCPlayerServiceTest extends TestCase
{
    public function testPirateIdAndUsername(): void
    {
        $npc = new NPCPlayerService('pirate', 5, 4, 3);
        $this->assertSame(-1, $npc->getId());
        $this->assertSame('Pirates', $npc->getUsername());
        $this->assertSame('pirate', $npc->getNpcType());
    }

    public function testAlienIdAndUsername(): void
    {
        $npc = new NPCPlayerService('alien', 8, 7, 6);
        $this->assertSame(-2, $npc->getId());
        $this->assertSame('Aliens', $npc->getUsername());
        $this->assertSame('alien', $npc->getNpcType());
    }

    public function testGetResearchLevelExposesOnlyCombatTechs(): void
    {
        $npc = new NPCPlayerService('pirate', 7, 5, 3);

        $this->assertSame(7, $npc->getResearchLevel('weapon_technology'));
        $this->assertSame(5, $npc->getResearchLevel('shielding_technology'));
        $this->assertSame(3, $npc->getResearchLevel('armor_technology'));

        // Anything else returns 0 — NPCs have no other tech.
        $this->assertSame(0, $npc->getResearchLevel('computer_technology'));
        $this->assertSame(0, $npc->getResearchLevel('hyperspace_technology'));
        $this->assertSame(0, $npc->getResearchLevel('astrophysics'));
        $this->assertSame(0, $npc->getResearchLevel('totally_unknown_tech'));
    }

    public function testPiratesAndAliensCanShareSameTechLevels(): void
    {
        // No constraint that pirate tech < alien tech inside the service itself;
        // the formula lives in NPCFleetGeneratorService.
        $pirate = new NPCPlayerService('pirate', 10, 10, 10);
        $alien = new NPCPlayerService('alien', 10, 10, 10);

        $this->assertSame(10, $pirate->getResearchLevel('weapon_technology'));
        $this->assertSame(10, $alien->getResearchLevel('weapon_technology'));
    }

    public function testIsAPlayerServiceInstance(): void
    {
        $npc = new NPCPlayerService('pirate', 0, 0, 0);
        $this->assertInstanceOf(PlayerService::class, $npc);
    }

    public function testZeroTechLevelsAreReportedAsZero(): void
    {
        $npc = new NPCPlayerService('pirate', 0, 0, 0);
        $this->assertSame(0, $npc->getResearchLevel('weapon_technology'));
        $this->assertSame(0, $npc->getResearchLevel('shielding_technology'));
        $this->assertSame(0, $npc->getResearchLevel('armor_technology'));
    }
}

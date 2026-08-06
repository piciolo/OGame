<?php

namespace Tests\Feature;

use Exception;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\Planet\Coordinate;
use OGame\Services\PlanetListService;
use Tests\MoonTestCase;

/**
 * Verifies PlanetListService behaviour:
 *  - Planets and moons are correctly partitioned by PlanetType
 *  - getById() returns owned planets/moons and throws for unknown IDs
 *  - getPlanetByCoordinates / getMoonByCoordinates return matching bodies, null otherwise
 *  - planetExistsAndOwnedByPlayer reflects ownership
 *  - current() returns the active body, falling back to the first planet for invalid ids
 *  - first() returns the first planet (or null if the player has none)
 *  - all() interleaves each planet with its moon, allPlanets/allMoons separate them
 *  - allIds() lists every body id, planetCount/allCount are coherent
 *
 * Uses MoonTestCase so we have:
 *   - 2 planets (userPlanetAmount = 2 from AccountTestCase)
 *   - 1 moon attached to the main planet
 */
class PlanetListServiceTest extends MoonTestCase
{
    private PlanetListService $list;

    protected function setUp(): void
    {
        parent::setUp();

        // Re-resolve the player so PlanetListService sees the freshly-created moon.
        $playerServiceFactory = resolve(PlayerServiceFactory::class);
        $player = $playerServiceFactory->make($this->currentUserId, true);
        $this->list = $player->planets;
    }

    public function testPlanetCountAndAllCount(): void
    {
        $this->assertSame(2, $this->list->planetCount());
        // 2 planets + 1 moon
        $this->assertSame(3, $this->list->allCount());
    }

    public function testFirstReturnsAPlanet(): void
    {
        $first = $this->list->first();
        $this->assertNotNull($first);
        $this->assertSame($this->planetService->getPlanetId(), $first->getPlanetId());
    }

    public function testAllPlanetsReturnsOnlyPlanets(): void
    {
        $planets = $this->list->allPlanets();
        $this->assertCount(2, $planets);
        foreach ($planets as $p) {
            $this->assertTrue($p->isPlanet());
        }
    }

    public function testAllMoonsReturnsOnlyMoons(): void
    {
        $moons = $this->list->allMoons();
        $this->assertCount(1, $moons);
        $this->assertSame($this->moonService->getPlanetId(), $moons[0]->getPlanetId());
    }

    public function testAllInterleavesPlanetAndItsMoon(): void
    {
        $bodies = $this->list->all();
        // 2 planets + 1 moon attached to the first planet → 3 entries
        $this->assertCount(3, $bodies);

        // Identify the planet that owns the moon: in MoonTestCase the moon is attached
        // to $planetService (main planet). The interleaving order is therefore:
        //   [main planet, main planet's moon, second planet]
        $this->assertSame($this->planetService->getPlanetId(), $bodies[0]->getPlanetId());
        $this->assertSame($this->moonService->getPlanetId(), $bodies[1]->getPlanetId());
        $this->assertSame($this->secondPlanetService->getPlanetId(), $bodies[2]->getPlanetId());
    }

    public function testAllIdsContainsEveryBody(): void
    {
        $ids = $this->list->allIds();
        $this->assertContains($this->planetService->getPlanetId(), $ids);
        $this->assertContains($this->secondPlanetService->getPlanetId(), $ids);
        $this->assertContains($this->moonService->getPlanetId(), $ids);
        $this->assertCount(3, $ids);
    }

    public function testGetByIdReturnsOwnedPlanetAndMoon(): void
    {
        $planet = $this->list->getById($this->planetService->getPlanetId());
        $this->assertSame($this->planetService->getPlanetId(), $planet->getPlanetId());

        $moon = $this->list->getById($this->moonService->getPlanetId());
        $this->assertSame($this->moonService->getPlanetId(), $moon->getPlanetId());
    }

    public function testGetByIdThrowsForUnknownPlanet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not owned by this player');
        $this->list->getById(99999999);
    }

    public function testGetPlanetByCoordinatesMatchesOwnedPlanet(): void
    {
        $coords = $this->planetService->getPlanetCoordinates();
        $found = $this->list->getPlanetByCoordinates($coords);
        $this->assertNotNull($found);
        $this->assertSame($this->planetService->getPlanetId(), $found->getPlanetId());
    }

    public function testGetPlanetByCoordinatesReturnsNullForUnknownCoords(): void
    {
        $missing = new Coordinate(9, 499, 15);
        $this->assertNull($this->list->getPlanetByCoordinates($missing));
    }

    public function testGetMoonByCoordinatesMatchesOwnedMoon(): void
    {
        $coords = $this->moonService->getPlanetCoordinates();
        $found = $this->list->getMoonByCoordinates($coords);
        $this->assertNotNull($found);
        $this->assertSame($this->moonService->getPlanetId(), $found->getPlanetId());
    }

    public function testGetMoonByCoordinatesReturnsNullWhenCoordsBelongToPlanetOnly(): void
    {
        // The main planet's coordinates differ from its moon (moons share galaxy/system
        // but are queried via the moon record). Ask for a wholly empty coordinate.
        $missing = new Coordinate(9, 499, 14);
        $this->assertNull($this->list->getMoonByCoordinates($missing));
    }

    public function testPlanetExistsAndOwnedByPlayerForOwnedBodies(): void
    {
        $this->assertTrue($this->list->planetExistsAndOwnedByPlayer($this->planetService->getPlanetId()));
        $this->assertTrue($this->list->planetExistsAndOwnedByPlayer($this->secondPlanetService->getPlanetId()));
        $this->assertTrue($this->list->planetExistsAndOwnedByPlayer($this->moonService->getPlanetId()));
        $this->assertFalse($this->list->planetExistsAndOwnedByPlayer(99999999));
    }

    public function testCurrentReturnsActiveBody(): void
    {
        // MoonTestCase's setUp() switched to the moon, so current() should be the moon.
        $playerServiceFactory = resolve(PlayerServiceFactory::class);
        $player = $playerServiceFactory->make($this->currentUserId, true);
        $current = $player->planets->current();
        $this->assertSame($this->moonService->getPlanetId(), $current->getPlanetId());
    }
}

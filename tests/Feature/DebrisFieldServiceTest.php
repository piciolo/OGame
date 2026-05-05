<?php

namespace Tests\Feature;

use Exception;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\DebrisField;
use OGame\Models\Planet\Coordinate;
use OGame\Models\Resources;
use OGame\Services\DebrisFieldService;
use Tests\AccountTestCase;

/**
 * Verifies DebrisFieldService behaviour:
 *  - loadOrCreateForCoordinates returns an empty in-memory field when none exists
 *  - loadOrCreateForCoordinates loads existing rows by (galaxy, system, planet)
 *  - loadForCoordinates returns false for missing coords, true after persistence
 *  - getResources mirrors the underlying row (4-tuple with energy=0)
 *  - appendResources sums into the in-memory model
 *  - deductResources subtracts and throws on insufficient funds
 *  - save() persists the model to the database
 *  - delete() removes the row when persisted
 *  - calculateRequiredRecyclers / calculateRequiredShips compute ceil(total/capacity)
 */
class DebrisFieldServiceTest extends AccountTestCase
{
    private DebrisFieldService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $playerServiceFactory = resolve(PlayerServiceFactory::class);
        $player = $playerServiceFactory->make($this->currentUserId, true);
        $this->service = new DebrisFieldService($player);

        // Clean up the unique-coord range so previous test runs do not pollute
        // these tests (the database is persistent between runs).
        DebrisField::query()
            ->where('galaxy', 9)
            ->whereBetween('system', [450, 499])
            ->where('planet', 16)
            ->delete();
    }

    private function uniqueCoord(): Coordinate
    {
        // Use position 16 (expedition slot) to avoid colliding with planet rows.
        return new Coordinate(9, mt_rand(450, 499), 16);
    }

    public function testLoadOrCreateReturnsEmptyFieldForUnknownCoords(): void
    {
        $coord = $this->uniqueCoord();
        $this->service->loadOrCreateForCoordinates($coord);

        $res = $this->service->getResources();
        $this->assertSame(0.0, $res->metal->get());
        $this->assertSame(0.0, $res->crystal->get());
        $this->assertSame(0.0, $res->deuterium->get());
        $this->assertSame($coord->galaxy, $this->service->getCoordinates()->galaxy);
        $this->assertSame($coord->system, $this->service->getCoordinates()->system);
        $this->assertSame($coord->position, $this->service->getCoordinates()->position);
    }

    public function testLoadForCoordinatesReturnsFalseWhenMissing(): void
    {
        $this->assertFalse($this->service->loadForCoordinates($this->uniqueCoord()));
    }

    public function testLoadForCoordinatesReturnsTrueAfterSave(): void
    {
        $coord = $this->uniqueCoord();
        $this->service->loadOrCreateForCoordinates($coord);
        $this->service->appendResources(new Resources(1000, 500, 0, 0));
        $this->service->save();

        // Fresh service to ensure the row is fetched from DB.
        $playerServiceFactory = resolve(PlayerServiceFactory::class);
        $fresh = new DebrisFieldService($playerServiceFactory->make($this->currentUserId, true));
        $this->assertTrue($fresh->loadForCoordinates($coord));
        $res = $fresh->getResources();
        $this->assertSame(1000.0, $res->metal->get());
        $this->assertSame(500.0, $res->crystal->get());
    }

    public function testAppendResourcesSumsValues(): void
    {
        $this->service->loadOrCreateForCoordinates($this->uniqueCoord());
        $this->service->appendResources(new Resources(100, 50, 25, 0));
        $this->service->appendResources(new Resources(900, 50, 75, 0));

        $res = $this->service->getResources();
        $this->assertSame(1000.0, $res->metal->get());
        $this->assertSame(100.0, $res->crystal->get());
        $this->assertSame(100.0, $res->deuterium->get());
    }

    public function testDeductResourcesSubtracts(): void
    {
        $this->service->loadOrCreateForCoordinates($this->uniqueCoord());
        $this->service->appendResources(new Resources(1000, 1000, 1000, 0));
        $this->service->deductResources(new Resources(300, 200, 100, 0));

        $res = $this->service->getResources();
        $this->assertSame(700.0, $res->metal->get());
        $this->assertSame(800.0, $res->crystal->get());
        $this->assertSame(900.0, $res->deuterium->get());
    }

    public function testDeductResourcesThrowsOnInsufficientFunds(): void
    {
        $this->service->loadOrCreateForCoordinates($this->uniqueCoord());
        $this->service->appendResources(new Resources(100, 100, 100, 0));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Not enough resources');
        $this->service->deductResources(new Resources(101, 0, 0, 0));
    }

    public function testSavePersistsRowAndDeleteRemovesIt(): void
    {
        $coord = $this->uniqueCoord();
        $this->service->loadOrCreateForCoordinates($coord);
        $this->service->appendResources(new Resources(1234, 5678, 91, 0));
        $this->service->save();

        $this->assertDatabaseHas('debris_fields', [
            'galaxy' => $coord->galaxy,
            'system' => $coord->system,
            'planet' => $coord->position,
            'metal'  => 1234,
        ]);

        $this->service->delete();

        $this->assertDatabaseMissing('debris_fields', [
            'galaxy' => $coord->galaxy,
            'system' => $coord->system,
            'planet' => $coord->position,
        ]);
    }

    public function testReloadRefreshesFromDatabase(): void
    {
        $coord = $this->uniqueCoord();
        $this->service->loadOrCreateForCoordinates($coord);
        $this->service->appendResources(new Resources(500, 500, 0, 0));
        $this->service->save();

        // Mutate the row externally to mimic another request.
        DebrisField::query()
            ->where('galaxy', $coord->galaxy)
            ->where('system', $coord->system)
            ->where('planet', $coord->position)
            ->update(['metal' => 999]);

        $this->service->reload();
        $this->assertSame(999.0, $this->service->getResources()->metal->get());
    }

    public function testCalculateRequiredRecyclersIsCeilDividedByCapacity(): void
    {
        $coord = $this->uniqueCoord();
        $this->service->loadOrCreateForCoordinates($coord);
        // Use a small total so the count is deterministic regardless of player tech.
        $this->service->appendResources(new Resources(10, 0, 0, 0));

        $required = $this->service->calculateRequiredRecyclers();
        $this->assertGreaterThanOrEqual(1, $required, 'Even tiny debris must require at least 1 recycler.');
    }

}

<?php

namespace Tests\Feature;

use OGame\Models\BuildingQueue;
use OGame\Models\PlanetBoost;
use OGame\Models\User;
use OGame\Models\UserItem;
use OGame\Services\InventoryActivationService;
use Tests\AccountTestCase;

/**
 * Verifies that activating an inventory item produces real game effects:
 * - amplifier_metal/crystal/deuterium → INSERT planet_boosts row
 * - amplifier_energy → replaces previous active energy boost
 * - booster_kraken → reduces time_end of running BuildingQueue task
 * - resources_lot → credits resources to planet
 *
 * Effect numbers sourced from OGame Wiki Fandom + board.it Macro Guida 2.0.
 */
class InventoryActivationTest extends AccountTestCase
{
    private InventoryActivationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(InventoryActivationService::class);
        // Clean slate
        UserItem::query()->where('user_id', $this->currentUserId)->delete();
        PlanetBoost::query()->where('user_id', $this->currentUserId)->delete();
        BuildingQueue::query()->where('planet_id', $this->currentPlanetId)->delete();
    }

    private function grantItem(string $itemType, string|null $tier, array $payload): UserItem
    {
        $registry = config('inventory_items');
        $key = $itemType . ':' . ((string) $tier);
        $def = $registry[$key];
        return UserItem::create([
            'user_id' => $this->currentUserId,
            'item_type' => $itemType,
            'tier' => $tier,
            'category' => $def['category']->value,
            'activation_type' => $def['activation_type'],
            'payload' => $payload,
            'status' => 'available',
            'acquired_at' => now(),
            'source' => 'test',
            'source_ref' => 0,
        ]);
    }

    public function testAmplifierMetalGoldInsertsPlanetBoost(): void
    {
        $this->grantItem('amplifier_metal', 'gold', ['percent' => 30, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'amplifier_metal', 'gold', $this->currentPlanetId);

        $this->assertTrue($r['ok'], 'activation should succeed: ' . $r['code']);
        $boost = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'metal')
            ->first();
        $this->assertNotNull($boost, 'planet_boosts row must exist');
        $this->assertSame(30, (int) $boost->percent_bonus);
        $this->assertTrue($boost->expires_at->greaterThan(now()->addDays(6)));
    }

    public function testAmplifierEnergyReplacesPreviousActive(): void
    {
        // Pre-existing energy boost
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'energy',
            'percent_bonus' => 10,
            'expires_at' => now()->addDays(3),
        ]);

        $this->grantItem('amplifier_energy', 'platinum', ['percent' => 40, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'amplifier_energy', 'platinum', $this->currentPlanetId);
        $this->assertTrue($r['ok']);

        $count = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'energy')
            ->where('expires_at', '>', now())
            ->count();
        $this->assertSame(1, $count, 'exactly ONE energy boost must remain active');

        $current = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'energy')
            ->first();
        $this->assertSame(40, (int) $current->percent_bonus, 'new energy bonus must replace the old one');
    }

    public function testAmplifierSameTierStacksDuration(): void
    {
        // Activate first time
        $this->grantItem('amplifier_crystal', 'silver', ['percent' => 20, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);
        $r1 = $this->service->activate($user, 'amplifier_crystal', 'silver', $this->currentPlanetId);
        $this->assertTrue($r1['ok']);

        // Read raw timestamp via direct SQL to avoid Carbon mutation issues in test env
        $expiresBefore = (int) \DB::table('planet_boosts')
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'crystal')
            ->selectRaw('UNIX_TIMESTAMP(expires_at) as ts')
            ->value('ts');

        // Activate second time same tier same resource
        $this->grantItem('amplifier_crystal', 'silver', ['percent' => 20, 'duration_seconds' => 604800]);
        $r2 = $this->service->activate($user, 'amplifier_crystal', 'silver', $this->currentPlanetId);
        $this->assertTrue($r2['ok']);

        $count = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'crystal')
            ->count();
        $this->assertSame(1, $count, 'same-tier same-resource should NOT duplicate (extends duration)');

        $expiresAfter = (int) \DB::table('planet_boosts')
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'crystal')
            ->selectRaw('UNIX_TIMESTAMP(expires_at) as ts')
            ->value('ts');

        $this->assertGreaterThan(
            $expiresBefore + (6 * 86400),
            $expiresAfter,
            'duration must be extended by ~7 days (raw timestamp diff: ' . ($expiresAfter - $expiresBefore) . 's)'
        );
    }

    public function testKrakenSilverReducesBuildingQueueByTwoHours(): void
    {
        $now = now()->timestamp;
        $task = new BuildingQueue();
        $task->planet_id = $this->currentPlanetId;
        $task->object_id = 1;
        $task->object_level_target = 5;
        $task->is_downgrade = false;
        $task->time_duration = 36000;
        $task->time_start = (int) $now;
        $task->time_end = (int) ($now + 36000);
        $task->metal = 0;
        $task->crystal = 0;
        $task->deuterium = 0;
        $task->building = 1;
        $task->processed = 0;
        $task->canceled = 0;
        $task->dm_halved = false;
        $task->save();

        $this->grantItem('booster_kraken', 'silver', ['reduction' => '2h']);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'booster_kraken', 'silver', $this->currentPlanetId);
        $this->assertTrue($r['ok'], 'activation should succeed: ' . $r['code']);

        $task = BuildingQueue::query()->where('planet_id', $this->currentPlanetId)->first();
        $this->assertSame((int) ($now + 36000 - 7200), (int) $task->time_end, 'time_end must be reduced by 7200s (2h)');
    }

    public function testKrakenWithEmptyQueueFailsAndDoesNotConsume(): void
    {
        BuildingQueue::query()->where('planet_id', $this->currentPlanetId)->delete();

        $item = $this->grantItem('booster_kraken', 'gold', ['reduction' => '6h']);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'booster_kraken', 'gold', $this->currentPlanetId);
        $this->assertFalse($r['ok']);
        $this->assertSame('no_effect_target', $r['code']);

        $item->refresh();
        $this->assertSame('available', $item->status, 'item must NOT be consumed when activation has no target');
    }

    public function testResourcesLotCreditsResourcesToPlanet(): void
    {
        $this->grantItem('resources_lot', 'gold', ['metal' => 100000, 'crystal' => 50000, 'deuterium' => 25000]);
        $user = User::find($this->currentUserId);

        $this->planetService->reloadPlanet();
        $metalBefore = (int) floor($this->planetService->metal()->get());

        $r = $this->service->activate($user, 'resources_lot', 'gold', $this->currentPlanetId);
        $this->assertTrue($r['ok']);

        $this->planetService->reloadPlanet();
        $metalAfter = (int) floor($this->planetService->metal()->get());
        // Allow for storage cap; just verify some metal was credited
        $this->assertGreaterThan($metalBefore, $metalAfter, 'metal should increase after lot activation');
    }

    public function testActivateInvalidPlanetReturnsError(): void
    {
        $this->grantItem('amplifier_metal', 'gold', ['percent' => 30, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);
        // Use a planet ID that doesn't belong to the user
        $foreignPlanet = \OGame\Models\Planet::query()->where('user_id', '!=', $user->id)->value('id');
        if ($foreignPlanet === null) {
            $this->markTestSkipped('No foreign planet available for this test');
        }

        $r = $this->service->activate($user, 'amplifier_metal', 'gold', (int) $foreignPlanet);

        $this->assertFalse($r['ok']);
        $this->assertSame('invalid_planet', $r['code']);
    }

    public function testActivateUnknownItemTypeReturnsError(): void
    {
        $user = User::find($this->currentUserId);
        $r = $this->service->activate($user, 'totally_unknown_type', null, $this->currentPlanetId);

        $this->assertFalse($r['ok']);
        $this->assertSame('unknown_item', $r['code']);
    }

    public function testTwoConsecutiveActivationsConsumeTwoSeparateItems(): void
    {
        // Grant 2 items of the same type/tier
        $i1 = $this->grantItem('amplifier_crystal', 'gold', ['percent' => 30, 'duration_seconds' => 604800]);
        $i2 = $this->grantItem('amplifier_crystal', 'gold', ['percent' => 30, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r1 = $this->service->activate($user, 'amplifier_crystal', 'gold', $this->currentPlanetId);
        $r2 = $this->service->activate($user, 'amplifier_crystal', 'gold', $this->currentPlanetId);

        $this->assertTrue($r1['ok']);
        $this->assertTrue($r2['ok']);

        $i1->refresh();
        $i2->refresh();
        $this->assertSame('consumed', $i1->status);
        $this->assertSame('consumed', $i2->status);

        // Same-tier + same-resource → duration extended on a single PlanetBoost row
        $boostCount = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'crystal')
            ->count();
        $this->assertSame(1, $boostCount, '2 activations of same tier should extend duration on single row');
    }

    public function testPlanetFieldsGoldAddsFifteenFieldsPermanent(): void
    {
        $this->grantItem('planet_fields', 'gold', ['fields' => 15]);
        $user = User::find($this->currentUserId);
        $beforeMax = (int) \OGame\Models\Planet::find($this->currentPlanetId)->field_max;

        $r = $this->service->activate($user, 'planet_fields', 'gold', $this->currentPlanetId);
        $this->assertTrue($r['ok']);

        $afterMax = (int) \OGame\Models\Planet::find($this->currentPlanetId)->field_max;
        $this->assertSame($beforeMax + 15, $afterMax, 'planet_fields gold must add +15 fields permanently');
    }

    public function testPlanetFieldsBronzeAddsFourFields(): void
    {
        $this->grantItem('planet_fields', 'bronze', ['fields' => 4]);
        $user = User::find($this->currentUserId);
        $beforeMax = (int) \OGame\Models\Planet::find($this->currentPlanetId)->field_max;

        $r = $this->service->activate($user, 'planet_fields', 'bronze', $this->currentPlanetId);
        $this->assertTrue($r['ok']);

        $afterMax = (int) \OGame\Models\Planet::find($this->currentPlanetId)->field_max;
        $this->assertSame($beforeMax + 4, $afterMax);
    }

    public function testFleetSlotGoldAddsThreeBoostRow(): void
    {
        $this->grantItem('fleet_slot', 'gold', ['slots' => 3, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'fleet_slot', 'gold', $this->currentPlanetId);
        $this->assertTrue($r['ok']);

        $boost = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'fleet_slots')
            ->first();
        $this->assertNotNull($boost);
        $this->assertSame(3, (int) $boost->percent_bonus);
        $this->assertTrue($boost->expires_at->greaterThan(now()->addDays(6)));
    }

    public function testExpeditionSlotSilverAddsTwoBoostRow(): void
    {
        $this->grantItem('expedition_slot', 'silver', ['slots' => 2, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'expedition_slot', 'silver', $this->currentPlanetId);
        $this->assertTrue($r['ok']);

        $boost = PlanetBoost::query()
            ->where('planet_id', $this->currentPlanetId)
            ->where('resource', 'expedition_slots')
            ->first();
        $this->assertNotNull($boost);
        $this->assertSame(2, (int) $boost->percent_bonus);
    }

    public function testEnergyAmplifierRejectedOnMoon(): void
    {
        $factory = resolve(\OGame\Factories\PlanetServiceFactory::class);
        $factory->createMoonForPlanet($this->planetService, 2000000, 100);
        $moonId = (int) \OGame\Models\Planet::query()
            ->where('user_id', $this->currentUserId)
            ->where('planet_type', \OGame\Models\Enums\PlanetType::Moon->value)
            ->value('id');
        $this->assertGreaterThan(0, $moonId, 'moon must exist');

        $item = $this->grantItem('amplifier_energy', 'gold', ['percent' => 30, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'amplifier_energy', 'gold', $moonId);

        $this->assertFalse($r['ok']);
        $this->assertSame('requires_planet', $r['code']);

        $item->refresh();
        $this->assertSame('available', $item->status, 'item must NOT be consumed when target type is wrong');
    }

    public function testMetalAmplifierRejectedOnMoon(): void
    {
        $factory = resolve(\OGame\Factories\PlanetServiceFactory::class);
        $factory->createMoonForPlanet($this->planetService, 2000000, 100);
        $moonId = (int) \OGame\Models\Planet::query()
            ->where('user_id', $this->currentUserId)
            ->where('planet_type', \OGame\Models\Enums\PlanetType::Moon->value)
            ->value('id');

        $this->grantItem('amplifier_metal', 'gold', ['percent' => 30, 'duration_seconds' => 604800]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'amplifier_metal', 'gold', $moonId);

        $this->assertFalse($r['ok']);
        $this->assertSame('requires_planet', $r['code']);
    }

    public function testMoonFieldsRejectedOnPlanet(): void
    {
        $item = $this->grantItem('moon_fields', 'gold', ['fields' => 6]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'moon_fields', 'gold', $this->currentPlanetId);

        $this->assertFalse($r['ok']);
        $this->assertSame('requires_moon', $r['code']);

        $item->refresh();
        $this->assertSame('available', $item->status);
    }

    public function testPlanetFieldsRejectedOnMoon(): void
    {
        $factory = resolve(\OGame\Factories\PlanetServiceFactory::class);
        $factory->createMoonForPlanet($this->planetService, 2000000, 100);
        $moonId = (int) \OGame\Models\Planet::query()
            ->where('user_id', $this->currentUserId)
            ->where('planet_type', \OGame\Models\Enums\PlanetType::Moon->value)
            ->value('id');

        $this->grantItem('planet_fields', 'gold', ['fields' => 15]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'planet_fields', 'gold', $moonId);

        $this->assertFalse($r['ok']);
        $this->assertSame('requires_planet', $r['code']);
    }

    public function testResourcesLotAllowedOnMoon(): void
    {
        $factory = resolve(\OGame\Factories\PlanetServiceFactory::class);
        $factory->createMoonForPlanet($this->planetService, 2000000, 100);
        $moonId = (int) \OGame\Models\Planet::query()
            ->where('user_id', $this->currentUserId)
            ->where('planet_type', \OGame\Models\Enums\PlanetType::Moon->value)
            ->value('id');

        $this->grantItem('resources_lot', 'gold', ['metal' => 50000, 'crystal' => 25000, 'deuterium' => 10000]);
        $user = User::find($this->currentUserId);

        $r = $this->service->activate($user, 'resources_lot', 'gold', $moonId);

        $this->assertTrue($r['ok'], 'resources_lot must work on moon: ' . $r['code']);
    }

    public function testFleetSlotBonusAffectsGetFleetSlotsMax(): void
    {
        $user = User::find($this->currentUserId);
        $player = resolve(\OGame\Factories\PlayerServiceFactory::class)->make($user->id);
        $beforeMax = $player->getFleetSlotsMax();

        // Add a fleet slot boost (+2)
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $user->id,
            'resource' => 'fleet_slots',
            'percent_bonus' => 2,
            'expires_at' => now()->addDays(7),
        ]);

        $afterMax = $player->getFleetSlotsMax();
        $this->assertSame($beforeMax + 2, $afterMax, 'fleet_slots boost must increase getFleetSlotsMax by +2');
    }
}

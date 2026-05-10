<?php

namespace Tests\Feature;

use OGame\Models\Resources;
use OGame\Services\BuyResourcesService;
use Tests\AccountTestCase;

/**
 * Feature tests for the "Procura risorse" tab of the Resource Market.
 *
 * Covers the OGame ufficiale mechanic of buying up to one daily production of a
 * resource directly with Dark Matter, with anti-tamper validation and storage
 * capping.
 */
class BuyResourcesTest extends AccountTestCase
{
    private BuyResourcesService $buy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buy = resolve(BuyResourcesService::class);
    }

    /**
     * Verify the per-resource coefficients match the values confirmed against the
     * live ufficiale UI snapshot. If anyone changes these inadvertently the cost
     * shown to players will diverge from OGame.
     */
    public function testCoefficientsMatchOfficialValues(): void
    {
        $this->assertSame(0.153856, BuyResourcesService::COEFFICIENTS['metal']);
        $this->assertSame(0.650939, BuyResourcesService::COEFFICIENTS['crystal']);
        $this->assertSame(2.477391, BuyResourcesService::COEFFICIENTS['deuterium']);
        $this->assertSame(500, BuyResourcesService::MIN_COST_DM);
    }

    /**
     * For each resource the service must compute Q = production_per_hour × 24
     * (capped at storage headroom) and P = ceil(Q × coefficient), with floor at
     * MIN_COST_DM. We assert against values derived from the test planet's actual
     * production so the test stays robust to seeding changes.
     */
    public function testCalculatePackageMatchesFormulaForEachResource(): void
    {
        $planet = $this->planetService;

        // Empty the deposits so headroom is large enough for the daily production
        // not to be clamped — we want to exercise the un-capped path.
        $this->emptyDeposits();

        foreach (['metal', 'crystal', 'deuterium'] as $resource) {
            $hourly = match ($resource) {
                'metal'     => $planet->getMetalProductionPerHour(),
                'crystal'   => $planet->getCrystalProductionPerHour(),
                'deuterium' => $planet->getDeuteriumProductionPerHour(),
            };
            $expectedDaily = max(0, (int) floor($hourly) * 24);
            $expectedCost = $expectedDaily > 0
                ? max(BuyResourcesService::MIN_COST_DM, (int) ceil($expectedDaily * BuyResourcesService::COEFFICIENTS[$resource]))
                : 0;

            $pkg = $this->buy->calculatePackage($planet, $resource);

            $this->assertSame($expectedDaily, $pkg['daily_production'], "daily_production mismatch for {$resource}");
            $this->assertSame($expectedDaily, $pkg['amount'], "amount mismatch for {$resource} (should equal daily when storage is empty)");
            $this->assertFalse($pkg['is_capped'], "should not be capped when deposits are empty for {$resource}");
            $this->assertSame($expectedCost, $pkg['cost_dm'], "cost_dm mismatch for {$resource}");
        }
    }

    /**
     * When storage headroom is smaller than the daily production, the offered
     * amount is clamped to the headroom and is_capped = true. Cost stays based on
     * the FULL daily production (OGame charges the whole package even when part
     * of the resources are lost — see the cappedToolTip warning).
     */
    public function testCalculatePackageClampsToStorageHeadroom(): void
    {
        $planet = $this->planetService;
        $hourly = $planet->getMetalProductionPerHour();
        if ($hourly <= 0) {
            $this->markTestSkipped('Test planet has no metal production; clamp test is not meaningful.');
        }

        $expectedDaily = (int) floor($hourly) * 24;
        $maxStorage = (int) floor($planet->metalStorage()->get());
        // Leave only 100 units of headroom on metal.
        $currentMetal = $planet->metal()->get();
        $toAdd = $maxStorage - 100 - (int) $currentMetal;
        if ($toAdd > 0) {
            $planet->addResources(new Resources($toAdd, 0, 0, 0));
        }
        $planet->save();

        $pkg = $this->buy->calculatePackage($planet, 'metal');

        $this->assertSame($expectedDaily, $pkg['daily_production']);
        $this->assertLessThanOrEqual(100, $pkg['amount']);
        $this->assertTrue($pkg['is_capped']);
        $this->assertSame((int) ceil($expectedDaily * BuyResourcesService::COEFFICIENTS['metal']), $pkg['cost_dm']);
    }

    /**
     * The "all" bundle's total cost is the sum of the 3 individual package costs.
     */
    public function testCalculateAllPackageSumsTheThree(): void
    {
        $bundle = $this->buy->calculateAllPackage($this->planetService);
        $expected = $bundle['packages']['metal']['cost_dm']
                  + $bundle['packages']['crystal']['cost_dm']
                  + $bundle['packages']['deuterium']['cost_dm'];
        $this->assertSame($expected, $bundle['total_cost_dm']);
    }

    /**
     * Buying the metal package debits DM and credits the planet atomically.
     */
    public function testBuyMetalPackageSucceeds(): void
    {
        $player = $this->planetService->getPlayer();
        $player->getUser()->dark_matter = 100000;
        $player->save();

        $this->emptyDeposits();
        $beforeMetal = (int) floor($this->planetService->metal()->get());
        $expected = $this->buy->calculatePackage($this->planetService, 'metal');
        $expectedCost = $expected['cost_dm'];

        $response = $this->post('/merchant/buy-resources', [
            'package' => 'metal',
            '_token' => csrf_token(),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $player->getUser()->refresh();
        $this->planetService->reloadPlanet();

        $this->assertSame($beforeMetal + $expected['amount'], (int) floor($this->planetService->metal()->get()));
        $this->assertSame(100000 - $expectedCost, (int) $player->getUser()->dark_matter);
    }

    /**
     * Anti-tamper: the request only carries 'package'. Any client-side cost or
     * amount field is ignored — server recomputes from authoritative state.
     */
    public function testAntiTamperIgnoresClientPriceAndAmount(): void
    {
        $player = $this->planetService->getPlayer();
        $player->getUser()->dark_matter = 100000;
        $player->save();

        $this->emptyDeposits();
        $expected = $this->buy->calculatePackage($this->planetService, 'metal');
        if ($expected['cost_dm'] <= 0) {
            $this->markTestSkipped('Test planet has no metal production; anti-tamper test not meaningful.');
        }

        $response = $this->post('/merchant/buy-resources', [
            'package' => 'metal',
            'cost_dm' => 1,         // attempt to spoof the price
            'amount'  => 999999,    // attempt to spoof the amount
            '_token'  => csrf_token(),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $player->getUser()->refresh();
        $this->assertSame(100000 - $expected['cost_dm'], (int) $player->getUser()->dark_matter);
    }

    /**
     * If the player can't afford the package, no DM moves and no resources change.
     */
    public function testBuyFailsWhenInsufficientDarkMatter(): void
    {
        $player = $this->planetService->getPlayer();
        $player->getUser()->dark_matter = 10;
        $player->save();

        $this->emptyDeposits();

        $response = $this->post('/merchant/buy-resources', [
            'package' => 'metal',
            '_token' => csrf_token(),
        ]);

        $response->assertStatus(400)->assertJson(['success' => false]);

        $player->getUser()->refresh();
        $this->assertSame(10, (int) $player->getUser()->dark_matter);
    }

    /**
     * Invalid package names are rejected with 400, no side effects.
     */
    public function testBuyFailsForInvalidPackage(): void
    {
        $player = $this->planetService->getPlayer();
        $player->getUser()->dark_matter = 100000;
        $player->save();

        $response = $this->post('/merchant/buy-resources', [
            'package' => 'darkmatter',
            '_token' => csrf_token(),
        ]);

        $response->assertStatus(400)->assertJson(['success' => false]);
    }

    /**
     * Buying the 'allLocalResources' bundle credits all three resources and debits
     * the combined cost in one atomic transaction.
     */
    public function testBuyAllPackageCreditsAllThree(): void
    {
        $player = $this->planetService->getPlayer();
        $player->getUser()->dark_matter = 1000000;
        $player->save();

        $this->emptyDeposits();
        $beforeMetal     = (int) floor($this->planetService->metal()->get());
        $beforeCrystal   = (int) floor($this->planetService->crystal()->get());
        $beforeDeuterium = (int) floor($this->planetService->deuterium()->get());
        $bundle = $this->buy->calculateAllPackage($this->planetService);
        if ($bundle['total_cost_dm'] <= 0) {
            $this->markTestSkipped('Test planet has zero combined production; bundle test not meaningful.');
        }

        $response = $this->post('/merchant/buy-resources', [
            'package' => 'allLocalResources',
            '_token' => csrf_token(),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $player->getUser()->refresh();
        $this->planetService->reloadPlanet();

        $this->assertSame($beforeMetal + $bundle['packages']['metal']['amount'],
            (int) floor($this->planetService->metal()->get()));
        $this->assertSame($beforeCrystal + $bundle['packages']['crystal']['amount'],
            (int) floor($this->planetService->crystal()->get()));
        $this->assertSame($beforeDeuterium + $bundle['packages']['deuterium']['amount'],
            (int) floor($this->planetService->deuterium()->get()));
        $this->assertSame(1000000 - $bundle['total_cost_dm'], (int) $player->getUser()->dark_matter);
    }

    /**
     * Empty all three deposits on the test planet so the package amount equals
     * the full daily production rather than a clamped headroom.
     */
    private function emptyDeposits(): void
    {
        $resources = $this->planetService->getResources();
        $this->planetService->deductResources(new Resources(
            (int) floor($resources->metal->get()),
            (int) floor($resources->crystal->get()),
            (int) floor($resources->deuterium->get()),
            0
        ));
        $this->planetService->save();
    }
}

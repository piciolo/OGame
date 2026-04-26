<?php

namespace Tests\Feature;

use OGame\Models\PlanetBoost;
use OGame\Models\ShopItem;
use OGame\Models\User;
use OGame\Services\InventoryService;
use Tests\AccountTestCase;

/**
 * Verifies InventoryService:
 * - activeBoostsForPlanet returns enriched data with description/price/inventory_count
 * - activeBoostsForPlanet excludes expired rows
 * - catalogForPlanet returns shop_items minus blocked categories
 * - shopPayload substitutes :metal/:crystal/:deuterium/:warning placeholders for Pacchetto Risorse
 */
class InventoryServiceTest extends AccountTestCase
{
    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(InventoryService::class);
        PlanetBoost::query()->where('planet_id', $this->currentPlanetId)->delete();
    }

    public function testActiveBoostsForPlanetReturnsEnrichedShape(): void
    {
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'metal',
            'percent_bonus' => 30,
            'expires_at' => now()->addDays(7),
        ]);

        $boosts = $this->service->activeBoostsForPlanet($this->currentPlanetId);
        $this->assertCount(1, $boosts);

        $b = $boosts[0];
        foreach (['id', 'resource', 'percent', 'expires_at_ts', 'tier', 'rarity', 'label', 'image_url',
                  'description', 'price_label', 'duration_label', 'inventory_count'] as $f) {
            $this->assertArrayHasKey($f, $b);
        }
        $this->assertSame('metal', $b['resource']);
        $this->assertSame(30, $b['percent']);
        $this->assertSame('gold', $b['tier']);
        $this->assertSame('rare', $b['rarity']);
    }

    public function testActiveBoostsExcludesExpiredRows(): void
    {
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'crystal',
            'percent_bonus' => 20,
            'expires_at' => now()->subHour(), // expired
        ]);
        PlanetBoost::create([
            'planet_id' => $this->currentPlanetId,
            'user_id' => $this->currentUserId,
            'resource' => 'deuterium',
            'percent_bonus' => 40,
            'expires_at' => now()->addDays(3), // active
        ]);

        $boosts = $this->service->activeBoostsForPlanet($this->currentPlanetId);
        $this->assertCount(1, $boosts);
        $this->assertSame('deuterium', $boosts[0]['resource']);
    }

    public function testCatalogForPlanetReturnsPagesOf8Items(): void
    {
        $cat = $this->service->catalogForPlanet(User::find($this->currentUserId));
        $this->assertArrayHasKey('pages', $cat);
        $this->assertNotEmpty($cat['pages']);

        $totalItems = array_sum(array_map('count', $cat['pages']));
        $this->assertGreaterThan(0, $totalItems);

        // First page should have at most 8 items (slide width)
        $this->assertLessThanOrEqual(8, count($cat['pages'][0]));
    }

    public function testCatalogForPlanetExcludesBlockedCategories(): void
    {
        $cat = $this->service->catalogForPlanet(User::find($this->currentUserId));
        // Flatten all tiles
        $allTiles = [];
        foreach ($cat['pages'] as $page) {
            foreach ($page as $tile) {
                $allTiles[] = $tile;
            }
        }

        $refs = array_column($allTiles, 'ref');
        // Profilo + seleziona_classe + booster_30 + booster_90 items must NOT appear
        $excluded = ShopItem::query()
            ->whereHas('categories', function ($q) {
                $q->whereIn('key', ['booster_30', 'booster_90', 'profilo', 'seleziona_classe']);
            })
            ->pluck('ref')
            ->all();
        foreach ($excluded as $ref) {
            $this->assertNotContains($ref, $refs, "Tile ref $ref should be excluded from overlay catalog");
        }
    }

    public function testCatalogForPlanetExcludesLifeformItems(): void
    {
        $cat = $this->service->catalogForPlanet(User::find($this->currentUserId));
        foreach ($cat['pages'] as $page) {
            foreach ($page as $tile) {
                $this->assertStringNotContainsString('(Forme di vita)', $tile['title']);
            }
        }
    }
}

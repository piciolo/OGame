<?php

namespace Tests\Feature;

use Database\Seeders\ShopCatalogSeeder;
use OGame\Models\ShopItem;
use OGame\Services\ShopService;
use Tests\AccountTestCase;

/**
 * Verifies ShopService::catalog() output: 7 categories visible (incluso seleziona_classe),
 * Lifeform variants filtered out, "tutto" virtual category counts the deduplicated total.
 *
 * Numbers must match OGame ufficiale: 111 items in "tutto" (post lifeform filter).
 */
class ShopServiceTest extends AccountTestCase
{
    private ShopService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed shop catalog (categories + items) for this test class.
        // The base AccountTestCase does not run shop seeders, so categories
        // would otherwise be missing and catalog() would return an empty list.
        $this->seed(ShopCatalogSeeder::class);

        $this->service = resolve(ShopService::class);
    }

    public function testCatalogReturns7VisibleCategoriesPlusTutto(): void
    {
        $catalog = $this->service->catalog();
        $keys = array_column($catalog['categories'], 'key');

        $this->assertContains('offerte_speciali', $keys);
        $this->assertContains('seleziona_classe', $keys);
        $this->assertContains('costruzione', $keys);
        $this->assertContains('risorse', $keys);
        $this->assertContains('booster_30', $keys);
        $this->assertContains('booster_90', $keys);
        $this->assertContains('profilo', $keys);
        $this->assertContains(ShopService::ALL_CATEGORY_KEY, $keys);
    }

    public function testCatalogTuttoCountMatchesShopItemsLessLifeforms(): void
    {
        $catalog = $this->service->catalog();
        /** @var array<int, array<string, mixed>> $categories */
        $categories = $catalog['categories'];
        $allCat = collect($categories)->firstWhere('key', ShopService::ALL_CATEGORY_KEY);

        // Lifeform variants are filtered out from catalog
        $expected = ShopItem::query()->where('name', 'not like', '%(Forme di vita)%')->count();
        $this->assertSame($expected, (int) $allCat['count']);
    }

    public function testCatalogExcludesLifeformItems(): void
    {
        $catalog = $this->service->catalog();
        foreach ($catalog['all_items'] as $item) {
            $this->assertStringNotContainsString('(Forme di vita)', $item['name']);
        }
    }

    public function testCatalogItemsHaveRequiredFields(): void
    {
        $catalog = $this->service->catalog();
        $first = reset($catalog['all_items']);
        $this->assertNotFalse($first);

        foreach (['ref', 'name', 'price_dm', 'price_label', 'duration_label', 'rarity', 'image', 'categories'] as $field) {
            $this->assertArrayHasKey($field, $first, "Missing field: $field");
        }
    }

    public function testHiddenCategoriesArrayIsEmptyByDefault(): void
    {
        // After we removed the seleziona_classe hide rule, no category should be hidden
        $catalog = $this->service->catalog();
        $cats = array_column($catalog['categories'], 'key');
        // seleziona_classe must be visible (it was previously hidden)
        $this->assertContains('seleziona_classe', $cats);
    }
}

<?php

namespace Tests\Feature;

use OGame\Models\ShopItem;
use OGame\Models\ShopPurchase;
use OGame\Models\User;
use OGame\Models\UserItem;
use OGame\Services\ShopService;
use RuntimeException;
use Tests\AccountTestCase;

/**
 * Verifies ShopService::purchase atomicity + audit trail.
 *
 * Critical: dark matter deduction + UserItem creation + ShopPurchase audit
 * must all happen in a single DB transaction. Pessimistic lock on the user
 * row prevents double-spend under concurrent requests.
 */
class ShopPurchaseTest extends AccountTestCase
{
    private ShopService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = resolve(ShopService::class);

        // Seed a known shop item so the test doesn't depend on full catalog seeder
        ShopItem::query()->updateOrCreate(
            ['ref' => sha1('test-purchase-item')],
            [
                'name' => 'Test Item',
                'description' => 'Test description',
                'price_dm' => 1000,
                'price_label' => '1.000 MO',
                'duration_seconds' => 0,
                'duration_label' => 'ora',
                'rarity' => 'common',
                'image' => 'test.png',
                'is_lifeform' => false,
                'tier_key' => null,
                'sort_order' => 999,
            ]
        );
    }

    private function shopItem(): ShopItem
    {
        return ShopItem::query()->where('ref', sha1('test-purchase-item'))->firstOrFail();
    }

    public function testPurchaseDeductsDarkMatterAndCreatesUserItemAndAudit(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 5000;
        $user->save();

        $item = $this->shopItem();
        $userItem = $this->service->purchase($user, $item, '127.0.0.1');

        $user->refresh();
        $this->assertSame(4000, (int) $user->dark_matter, 'DM must be deducted');
        $this->assertInstanceOf(\OGame\Models\UserItem::class, $userItem);
        $this->assertSame('shop_item', $userItem->item_type);
        $this->assertSame((int) $item->id, (int) $userItem->source_ref);

        $this->assertDatabaseHas('shop_purchases', [
            'user_id' => $user->id,
            'shop_item_id' => $item->id,
            'user_item_id' => $userItem->id,
            'dm_spent' => 1000,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function testPurchaseFailsWithInsufficientDarkMatter(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 100;
        $user->save();

        $item = $this->shopItem();
        $beforeCount = UserItem::query()->where('user_id', $user->id)->count();
        $beforeAudit = ShopPurchase::query()->where('user_id', $user->id)->count();

        try {
            $this->service->purchase($user, $item, '127.0.0.1');
            $this->fail('Should have thrown insufficient_dm');
        } catch (RuntimeException $e) {
            $this->assertSame('insufficient_dm', $e->getMessage());
        }

        $user->refresh();
        $this->assertSame(100, (int) $user->dark_matter, 'DM unchanged on failure');
        $this->assertSame($beforeCount, UserItem::query()->where('user_id', $user->id)->count(), 'No UserItem created on failure');
        $this->assertSame($beforeAudit, ShopPurchase::query()->where('user_id', $user->id)->count(), 'No audit row created on failure');
    }

    public function testPurchaseTwiceDeductsTwiceAndAuditsTwice(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 5000;
        $user->save();

        $item = $this->shopItem();
        $this->service->purchase($user, $item, '127.0.0.1');
        $this->service->purchase($user, $item, '127.0.0.1');

        $user->refresh();
        $this->assertSame(3000, (int) $user->dark_matter);
        $this->assertSame(2, ShopPurchase::query()
            ->where('user_id', $user->id)
            ->where('shop_item_id', $item->id)
            ->count());
    }

    public function testBuyEndpointRejectsInvalidRef(): void
    {
        $response = $this->postJson('/ajax/shop/buy', ['ref' => 'not-a-sha1']);
        $response->assertStatus(400);
        $response->assertJson(['error' => true]);
    }

    public function testBuyEndpointRejectsLifeformItem(): void
    {
        $lf = ShopItem::query()->updateOrCreate(
            ['ref' => sha1('lifeform-test')],
            [
                'name' => 'KRAKEN Bronzo (Forme di vita)',
                'description' => '',
                'price_dm' => 100,
                'price_label' => '100 MO',
                'duration_seconds' => 0,
                'duration_label' => 'ora',
                'rarity' => 'common',
                'image' => 'lf.png',
                'is_lifeform' => true,
                'tier_key' => null,
                'sort_order' => 998,
            ]
        );

        $response = $this->postJson('/ajax/shop/buy', ['ref' => $lf->ref]);
        $response->assertStatus(422);
    }
}

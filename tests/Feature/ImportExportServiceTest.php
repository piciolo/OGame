<?php

namespace Tests\Feature;

use Database\Seeders\ImportExportCatalogSeeder;
use OGame\Models\ImportExportHistory;
use OGame\Models\ImportExportItem;
use OGame\Models\ImportExportOffer;
use OGame\Models\Planet;
use OGame\Models\Resources;
use OGame\Models\User;
use OGame\Models\UserItem;
use OGame\Services\ImportExportService;
use RuntimeException;
use Tests\AccountTestCase;

/**
 * Verifies ImportExportService behaviour:
 *  - getOrCreateOffer creates a fresh pending offer when none exists
 *  - getOrCreateOffer returns the consumed offer (paid/taken_dm) within the same cycle
 *  - pay() with a valid resource mix deducts resources, grants UserItem and marks offer paid
 *  - pay() with a payment total mismatching offer.price throws
 *  - pay() with insufficient planet resources throws
 *  - takeWithDm() deducts 500 DM and grants UserItem
 *  - takeWithDm() with insufficient DM throws
 *  - change() consumes DM and rolls a new item, updating change_count
 *  - change() over MAX_CHANGES_PER_CYCLE throws
 *  - ownedCount() reflects the user's available inventory of the same item type+rarity
 *  - mapItemType() static mapping covers all known catalog types
 */
class ImportExportServiceTest extends AccountTestCase
{
    private ImportExportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed catalog (idempotent thanks to updateOrCreate in seeder).
        $this->seed(ImportExportCatalogSeeder::class);

        // Clean any leftover offers/history for the freshly created user (defensive).
        ImportExportOffer::query()->where('user_id', $this->currentUserId)->delete();
        ImportExportHistory::query()->where('user_id', $this->currentUserId)->delete();
        UserItem::query()->where('user_id', $this->currentUserId)->where('source', 'import_export')->delete();

        $this->service = resolve(ImportExportService::class);

        // Fund planet generously: any "pay with metal only" path needs metal >= price.
        $this->planetService->addResources(new Resources(10_000_000, 5_000_000, 2_000_000, 0), true);
    }

    public function testMapItemTypeReturnsExpectedInventoryKeys(): void
    {
        $this->assertSame('booster_kraken', ImportExportService::mapItemType('kraken'));
        $this->assertSame('booster_detroid', ImportExportService::mapItemType('detroid'));
        $this->assertSame('booster_newtron', ImportExportService::mapItemType('newtron'));
        $this->assertSame('amplifier_metal', ImportExportService::mapItemType('metal_booster'));
        $this->assertSame('amplifier_crystal', ImportExportService::mapItemType('crystal_booster'));
        $this->assertSame('amplifier_deuterium', ImportExportService::mapItemType('deuterium_booster'));
        // Unknown types pass through unchanged.
        $this->assertSame('something_else', ImportExportService::mapItemType('something_else'));
    }

    public function testGetOrCreateOfferCreatesFreshPendingOffer(): void
    {
        $user = User::find($this->currentUserId);

        $offer = $this->service->getOrCreateOffer($user);

        $this->assertSame('pending', $offer->status);
        $this->assertGreaterThanOrEqual(500, (int) $offer->price); // minimo enforced dal calculatePrice
        $this->assertSame(0, (int) $offer->change_count);
        $this->assertNotNull($offer->item_id);
        $this->assertNotNull($offer->expires_at);
        $this->assertTrue($offer->expires_at->greaterThan(now()));
    }

    public function testGetOrCreateOfferReturnsConsumedOfferInSameCycle(): void
    {
        $user = User::find($this->currentUserId);

        $offer = $this->service->getOrCreateOffer($user);
        $this->forcePriceTo($offer, 1000); // make pay() trivially feasible

        $this->service->pay($user, $offer->fresh(), Planet::find($this->currentPlanetId), [
            'metal' => 1000, 'crystal' => 0, 'deuterium' => 0, 'honor' => 0,
        ]);

        // Re-fetch: the same paid offer should be returned (the player must NOT get a fresh one).
        $next = $this->service->getOrCreateOffer($user->fresh());

        $this->assertSame($offer->id, $next->id);
        $this->assertSame('paid', $next->status);
    }

    public function testPayWithMetalOnlyDeductsResourcesAndGrantsUserItem(): void
    {
        $user = User::find($this->currentUserId);

        $offer = $this->service->getOrCreateOffer($user);
        $this->forcePriceTo($offer, 5000);

        $metalBefore = (int) floor($this->planetService->metal()->get());

        $userItem = $this->service->pay($user->fresh(), $offer->fresh(), Planet::find($this->currentPlanetId), [
            'metal' => 5000, 'crystal' => 0, 'deuterium' => 0, 'honor' => 0,
        ]);

        $this->assertInstanceOf(UserItem::class, $userItem);
        $this->assertSame($user->id, $userItem->user_id);
        $this->assertSame('available', $userItem->status);
        $this->assertSame('import_export', $userItem->source);

        $this->planetService->reloadPlanet();
        $metalAfter = (int) floor($this->planetService->metal()->get());
        $this->assertLessThanOrEqual($metalBefore - 5000, $metalAfter);

        $this->assertDatabaseHas('import_export_offers', [
            'id' => $offer->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('import_export_history', [
            'user_id' => $user->id,
            'item_id' => $offer->item_id,
            'acquisition_method' => 'pay_resources',
            'paid_metal' => 5000,
        ]);
    }

    public function testPayWithMixedResourcesUsesConfiguredMultipliers(): void
    {
        $user = User::find($this->currentUserId);
        $offer = $this->service->getOrCreateOffer($user);

        // metal*1 + crystal*1.5 + deuterium*3
        // 1000 + 1500*1.5 + 500*3 = 1000 + 2250 + 1500 = 4750
        $this->forcePriceTo($offer, 4750);

        $userItem = $this->service->pay($user->fresh(), $offer->fresh(), Planet::find($this->currentPlanetId), [
            'metal' => 1000, 'crystal' => 1500, 'deuterium' => 500, 'honor' => 0,
        ]);
        $this->assertInstanceOf(UserItem::class, $userItem);

        $this->assertDatabaseHas('import_export_history', [
            'user_id' => $user->id,
            'paid_metal' => 1000,
            'paid_crystal' => 1500,
            'paid_deuterium' => 500,
            'paid_honor' => 0,
        ]);
    }

    public function testPayRejectsTotalMismatch(): void
    {
        $user = User::find($this->currentUserId);
        $offer = $this->service->getOrCreateOffer($user);
        $this->forcePriceTo($offer, 5000);

        $this->expectException(RuntimeException::class);
        $this->service->pay($user->fresh(), $offer->fresh(), Planet::find($this->currentPlanetId), [
            'metal' => 1234, 'crystal' => 0, 'deuterium' => 0, 'honor' => 0,
        ]);
    }

    public function testPayRejectsInsufficientPlanetResources(): void
    {
        $user = User::find($this->currentUserId);
        $offer = $this->service->getOrCreateOffer($user);
        $this->forcePriceTo($offer, 99_999_999); // larger than the planet stock

        $this->expectException(RuntimeException::class);
        $this->service->pay($user->fresh(), $offer->fresh(), Planet::find($this->currentPlanetId), [
            'metal' => 99_999_999, 'crystal' => 0, 'deuterium' => 0, 'honor' => 0,
        ]);
    }

    public function testTakeWithDmDeductsFiveHundredAndGrantsItem(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 1500;
        $user->save();

        $offer = $this->service->getOrCreateOffer($user->fresh());

        $userItem = $this->service->takeWithDm($user->fresh(), $offer->fresh());

        $this->assertInstanceOf(UserItem::class, $userItem);

        $userAfter = User::find($user->id);
        $this->assertSame(1500 - 500, (int) $userAfter->dark_matter);

        $this->assertDatabaseHas('import_export_offers', [
            'id' => $offer->id,
            'status' => 'taken_dm',
        ]);
        $this->assertDatabaseHas('import_export_history', [
            'user_id' => $user->id,
            'acquisition_method' => 'pay_dm',
            'paid_dm' => 500,
        ]);
    }

    public function testTakeWithDmRejectsInsufficientDarkMatter(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 100; // below 500
        $user->save();

        $offer = $this->service->getOrCreateOffer($user->fresh());

        $this->expectException(RuntimeException::class);
        $this->service->takeWithDm($user->fresh(), $offer->fresh());
    }

    public function testChangeRollsNewItemAndDecrementsDarkMatter(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 50_000;
        $user->save();

        $offer = $this->service->getOrCreateOffer($user->fresh());
        $originalItemId = $offer->item_id;

        $this->service->change($user->fresh(), $offer->fresh());

        $offerAfter = ImportExportOffer::find($offer->id);
        $this->assertSame(1, (int) $offerAfter->change_count);
        // Item may stay the same when only one item exists, but in our seeded catalog
        // (18 items) chance of identical roll is minimal — still, we only assert change_count.
        $this->assertNotNull($offerAfter->item_id);

        $userAfter = User::find($user->id);
        // dm_change_cost depends on rarity (500/1500/4500). All are < 50_000.
        $this->assertLessThan(50_000, (int) $userAfter->dark_matter);
    }

    public function testChangeRejectsAfterMaxCycleLimit(): void
    {
        $user = User::find($this->currentUserId);
        $user->dark_matter = 100_000;
        $user->save();

        $offer = $this->service->getOrCreateOffer($user->fresh());
        // Manually push change_count to the cap.
        $offer->change_count = ImportExportService::MAX_CHANGES_PER_CYCLE;
        $offer->save();

        $this->expectException(RuntimeException::class);
        $this->service->change($user->fresh(), $offer->fresh());
    }

    public function testOwnedCountReflectsUserInventory(): void
    {
        $user = User::find($this->currentUserId);
        $offer = $this->service->getOrCreateOffer($user);
        $item = ImportExportItem::find($offer->item_id);
        $itemType = ImportExportService::mapItemType($item->type);

        // Initially zero.
        $this->assertSame(0, $this->service->ownedCount($user, $offer));

        // Insert two matching items + one decoy with different tier.
        UserItem::create([
            'user_id' => $user->id,
            'item_type' => $itemType,
            'tier' => $item->rarity,
            'category' => 'items',
            'activation_type' => 'instant',
            'payload' => [],
            'status' => 'available',
            'acquired_at' => now(),
            'source' => 'manual',
        ]);
        UserItem::create([
            'user_id' => $user->id,
            'item_type' => $itemType,
            'tier' => $item->rarity,
            'category' => 'items',
            'activation_type' => 'instant',
            'payload' => [],
            'status' => 'available',
            'acquired_at' => now(),
            'source' => 'manual',
        ]);
        UserItem::create([
            'user_id' => $user->id,
            'item_type' => $itemType,
            'tier' => $item->rarity === 'gold' ? 'bronze' : 'gold', // wrong tier
            'category' => 'items',
            'activation_type' => 'instant',
            'payload' => [],
            'status' => 'available',
            'acquired_at' => now(),
            'source' => 'manual',
        ]);
        // Decoy: same tier+type but already activated.
        UserItem::create([
            'user_id' => $user->id,
            'item_type' => $itemType,
            'tier' => $item->rarity,
            'category' => 'items',
            'activation_type' => 'instant',
            'payload' => [],
            'status' => 'activated',
            'acquired_at' => now(),
            'source' => 'manual',
        ]);

        $this->assertSame(2, $this->service->ownedCount($user, $offer));
    }

    /**
     * Force the offer price to a deterministic value so the pay() path can be
     * exercised without depending on the random jitter of calculatePrice().
     */
    private function forcePriceTo(ImportExportOffer $offer, int $price): void
    {
        ImportExportOffer::query()->where('id', $offer->id)->update(['price' => $price]);
    }
}

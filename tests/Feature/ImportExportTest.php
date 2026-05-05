<?php

namespace Tests\Feature;

use Database\Seeders\ImportExportCatalogSeeder;
use OGame\Models\ImportExportHistory;
use OGame\Models\ImportExportOffer;
use OGame\Models\User;
use OGame\Models\UserItem;
use OGame\Services\ImportExportService;
use Tests\AccountTestCase;

/**
 * HTTP end-to-end tests for the Import/Export trader:
 *  - GET /merchant/import-export renders the page and initialises a daily offer
 *  - GET /ajax/merchant/import-export returns the AJAX partial body
 *  - POST /merchant/import-export/pay deducts resources and grants UserItem
 *  - POST /merchant/import-export/take consumes 500 DM and grants UserItem
 *  - POST /merchant/import-export/change rolls a new item and decrements DM
 *  - Insufficient resources / DM produce a redirect with an error session flash
 */
class ImportExportTest extends AccountTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ImportExportCatalogSeeder::class);

        // Defensive cleanup for the freshly created user.
        ImportExportOffer::query()->where('user_id', $this->currentUserId)->delete();
        ImportExportHistory::query()->where('user_id', $this->currentUserId)->delete();
        UserItem::query()->where('user_id', $this->currentUserId)->where('source', 'import_export')->delete();
    }

    public function testIndexRendersAndCreatesPendingOffer(): void
    {
        $response = $this->get(route('importexport.index'));
        $response->assertStatus(200);

        $offer = ImportExportOffer::query()
            ->where('user_id', $this->currentUserId)
            ->where('status', 'pending')
            ->first();
        $this->assertNotNull($offer, 'An import/export pending offer should be created on first visit.');
    }

    public function testAjaxPartialReturnsTwoHundred(): void
    {
        $response = $this->get(route('importexport.partial'));
        $response->assertStatus(200);
    }

    public function testPayWithMetalRedirectsAndGrantsUserItem(): void
    {
        // Trigger offer creation and pin price for deterministic payment.
        $this->get(route('importexport.index'));
        $offer = ImportExportOffer::query()
            ->where('user_id', $this->currentUserId)
            ->where('status', 'pending')
            ->firstOrFail();
        ImportExportOffer::query()->where('id', $offer->id)->update(['price' => 5000]);

        // Make sure the planet has enough metal.
        $this->planetService->addResources(new \OGame\Models\Resources(50_000, 0, 0, 0), true);

        $response = $this->post(route('importexport.pay'), [
            'planet_id' => $this->currentPlanetId,
            'metal'     => 5000,
            'crystal'   => 0,
            'deuterium' => 0,
            'honor'     => 0,
        ]);

        $response->assertRedirect(route('importexport.index'));
        $response->assertSessionMissing('error');

        $this->assertDatabaseHas('import_export_offers', [
            'id'     => $offer->id,
            'status' => 'paid',
        ]);
        $this->assertDatabaseHas('user_items', [
            'user_id'     => $this->currentUserId,
            'source'      => 'import_export',
            'source_ref'  => $offer->id,
            'status'      => 'available',
        ]);
    }

    public function testPayWithMismatchedTotalFlashesError(): void
    {
        $this->get(route('importexport.index'));
        $offer = ImportExportOffer::query()
            ->where('user_id', $this->currentUserId)
            ->where('status', 'pending')
            ->firstOrFail();
        ImportExportOffer::query()->where('id', $offer->id)->update(['price' => 5000]);

        $this->planetService->addResources(new \OGame\Models\Resources(50_000, 0, 0, 0), true);

        $response = $this->post(route('importexport.pay'), [
            'planet_id' => $this->currentPlanetId,
            'metal'     => 1234, // total mismatch vs price=5000
            'crystal'   => 0,
            'deuterium' => 0,
            'honor'     => 0,
        ]);

        $response->assertRedirect(route('importexport.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('import_export_offers', [
            'id'     => $offer->id,
            'status' => 'pending', // unchanged
        ]);
    }

    public function testTakeWithDmConsumesFiveHundredAndGrantsItem(): void
    {
        $this->get(route('importexport.index'));
        $offer = ImportExportOffer::query()
            ->where('user_id', $this->currentUserId)
            ->where('status', 'pending')
            ->firstOrFail();

        $user = User::find($this->currentUserId);
        $user->dark_matter = 1000;
        $user->save();

        $response = $this->post(route('importexport.take'));
        $response->assertRedirect(route('importexport.index'));
        $response->assertSessionMissing('error');

        $userAfter = User::find($this->currentUserId);
        $this->assertSame(1000 - ImportExportService::TAKE_ITEM_DM_COST, (int) $userAfter->dark_matter);

        $this->assertDatabaseHas('import_export_offers', [
            'id'     => $offer->id,
            'status' => 'taken_dm',
        ]);
        $this->assertDatabaseHas('user_items', [
            'user_id'    => $this->currentUserId,
            'source'     => 'import_export',
            'source_ref' => $offer->id,
        ]);
    }

    public function testTakeWithInsufficientDmFlashesError(): void
    {
        $this->get(route('importexport.index'));

        $user = User::find($this->currentUserId);
        $user->dark_matter = 100; // < 500
        $user->save();

        $response = $this->post(route('importexport.take'));
        $response->assertRedirect(route('importexport.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('import_export_offers', [
            'user_id' => $this->currentUserId,
            'status'  => 'taken_dm',
        ]);
    }

    public function testChangeConsumesDarkMatterAndIncrementsCounter(): void
    {
        $this->get(route('importexport.index'));
        $offer = ImportExportOffer::query()
            ->where('user_id', $this->currentUserId)
            ->where('status', 'pending')
            ->firstOrFail();

        $user = User::find($this->currentUserId);
        $user->dark_matter = 50_000;
        $user->save();

        $response = $this->post(route('importexport.change'));
        $response->assertRedirect(route('importexport.index'));
        $response->assertSessionMissing('error');

        $offerAfter = ImportExportOffer::find($offer->id);
        $this->assertSame(1, (int) $offerAfter->change_count);

        $userAfter = User::find($this->currentUserId);
        $this->assertLessThan(50_000, (int) $userAfter->dark_matter);
    }
}

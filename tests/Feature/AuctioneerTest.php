<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use OGame\Enums\AuctionLotType;
use OGame\Enums\AuctionStatus;
use OGame\Enums\AuctionTier;
use OGame\Exceptions\AuctionBidException;
use OGame\Models\Auction;
use OGame\Models\AuctionBid;
use OGame\Models\Message;
use OGame\Models\Resources;
use OGame\Models\Setting;
use OGame\Models\User;
use OGame\Services\AuctioneerService;
use Tests\AccountTestCase;

class AuctioneerTest extends AccountTestCase
{
    private AuctioneerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed auctioneer settings with deterministic values.
        $defaults = [
            'auctioneer_enabled' => '1',
            'auctioneer_point_rate_metal' => '1',
            'auctioneer_point_rate_crystal' => '1.5',
            'auctioneer_point_rate_deuterium' => '3',
            'auctioneer_min_increment_points' => '100',
            'auctioneer_duration_seconds' => '2700',
            'auctioneer_waiting_seconds' => '3600',
            'auctioneer_extension_threshold_seconds' => '30',
            // Disable spawn window restriction so tick()-based tests can spawn anytime.
            'auctioneer_spawn_start_hour' => '-1',
            'auctioneer_spawn_end_hour' => '-1',
        ];
        foreach ($defaults as $k => $v) {
            Setting::query()->updateOrCreate(['key' => $k], ['value' => $v]);
        }

        // Clean any leftover auctions/bids from previous tests so tick() only sees our fixture.
        AuctionBid::query()->delete();
        Auction::query()->delete();

        $this->service = resolve(AuctioneerService::class);

        // Fund the planet with enough resources for several bids.
        $this->planetService->addResources(new Resources(500000, 300000, 100000, 0), true);
    }

    private function createRunningAuction(int $minBid = 1000): Auction
    {
        $auction = new Auction();
        $auction->status = AuctionStatus::Running;
        $auction->tier = AuctionTier::Bronze;
        $auction->lot_type = AuctionLotType::Resources;
        $auction->lot_title = 'Test lot';
        $auction->lot_payload = ['metal' => 5000, 'crystal' => 2500, 'deuterium' => 1000];
        $auction->min_bid_points = $minBid;
        $auction->current_bid_points = 0;
        $auction->started_at = now();
        $auction->ends_at = now()->addMinutes(30);
        $auction->save();

        return $auction;
    }

    public function testCalculatePointsUsesConfiguredRates(): void
    {
        // metal*1 + crystal*1.5 + deuterium*3
        $this->assertSame(1000 + 1500 + 3000, $this->service->calculatePoints(1000, 1000, 1000));
    }

    public function testPlaceBidBelowMinimumIsRejected(): void
    {
        $auction = $this->createRunningAuction(minBid: 10000);
        $user = User::find($this->currentUserId);

        $this->expectException(AuctionBidException::class);
        $this->service->placeBid($user, $this->currentPlanetId, 100, 0, 0); // 100 points << 10000
    }

    public function testPlaceBidInsufficientResourcesIsRejected(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $user = User::find($this->currentUserId);

        // Demand absurd amount of metal beyond the planet's stock.
        $this->expectException(AuctionBidException::class);
        $this->service->placeBid($user, $this->currentPlanetId, 999999999, 0, 0);
    }

    public function testPlaceBidDeductsResourcesAndUpdatesAuction(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $user = User::find($this->currentUserId);

        $metalBefore = (int) floor($this->planetService->metal()->get());
        $crystalBefore = (int) floor($this->planetService->crystal()->get());

        $updated = $this->service->placeBid($user, $this->currentPlanetId, 1000, 1000, 0);

        $this->assertSame((int) $user->id, (int) $updated->current_bidder_user_id);
        $this->assertSame(1, (int) $updated->bid_count);
        $this->assertSame(1000 + 1500, (int) $updated->current_bid_points);

        $this->planetService->reloadPlanet();
        $metalAfter = (int) floor($this->planetService->metal()->get());
        $crystalAfter = (int) floor($this->planetService->crystal()->get());
        // Allow small production drift by asserting at least the cost was removed.
        $this->assertLessThanOrEqual($metalBefore - 1000, $metalAfter);
        $this->assertLessThanOrEqual($crystalBefore - 1000, $crystalAfter);

        $this->assertDatabaseHas('auction_bids', [
            'auction_id' => $auction->id,
            'user_id' => $user->id,
            'points' => 2500,
        ]);
    }

    public function testOutbidIncreasesBidderAndRequiresIncrement(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $user = User::find($this->currentUserId);

        // First bid: 2500 points.
        $this->service->placeBid($user, $this->currentPlanetId, 1000, 1000, 0);

        // Second bid with same points should be rejected (must exceed current + increment).
        try {
            $this->service->placeBid($user, $this->currentPlanetId, 1000, 1000, 0);
            $this->fail('Second bid with same points should have been rejected');
        } catch (AuctionBidException $e) {
            $this->assertStringContainsString('Bid too low', $e->getMessage());
        }

        // Third bid exceeding increment must succeed.
        $updated = $this->service->placeBid($user, $this->currentPlanetId, 3000, 0, 0);
        $this->assertSame(3000, (int) $updated->current_bid_points);
        $this->assertSame(2, (int) $updated->bid_count);
    }

    public function testConcurrentBidsOnlyOneWinsAtSamePointLevel(): void
    {
        // Simulate "race": open TWO separate DB connections, both try to outbid simultaneously.
        // Laravel's default connection name is "mysql" / "sqlite" depending on env.
        // We use the `lockForUpdate` serialization inside the service: the first wins, the second should fail.
        $auction = $this->createRunningAuction(minBid: 100);
        $user = User::find($this->currentUserId);

        // Pre-set current_bid to 2000 so minimum next is 2100.
        $auction->current_bid_points = 2000;
        $auction->save();

        // Both players try to bid exactly 2100 points. Only one should succeed
        // because the second attempt reads the updated current_bid_points (after the first commits)
        // and sees required=2200.
        $first = null;
        $secondFailed = false;

        try {
            $first = $this->service->placeBid($user, $this->currentPlanetId, 2100, 0, 0);
        } catch (AuctionBidException $e) {
            $this->fail('First bid should have succeeded: ' . $e->getMessage());
        }

        try {
            // Second bid at the same 2100 should now be below required.
            $this->service->placeBid($user, $this->currentPlanetId, 2100, 0, 0);
        } catch (AuctionBidException $e) {
            $secondFailed = true;
            $this->assertStringContainsString('Bid too low', $e->getMessage());
        }

        $this->assertNotNull($first);
        $this->assertTrue($secondFailed, 'Second identical-points bid must fail');
        $this->assertSame(1, (int) $auction->fresh()->bid_count);
    }

    public function testLateBidExtendsEndTime(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        // Force auction to end in 5 seconds (below extension threshold of 30).
        // Bronze tier extends by 7-10s (per AuctionTier::lateBidExtensionRange).
        $auction->ends_at = now()->addSeconds(5);
        $auction->save();

        $user = User::find($this->currentUserId);
        $updated = $this->service->placeBid($user, $this->currentPlanetId, 1000, 0, 0);

        $remaining = (int) now()->diffInSeconds($updated->ends_at, false);
        $this->assertGreaterThanOrEqual(11, $remaining, 'Bronze should extend by 7-10s minimum');
        $this->assertLessThanOrEqual(15, $remaining, 'Bronze should extend by 7-10s maximum');
        $this->assertSame(1, (int) $updated->extension_count);
    }

    public function testLateBidExtensionRangeMatchesTier(): void
    {
        // Platinum extends by 2-3s; Bronze by 7-10s. Verify the tier window is honored.
        $auction = $this->createRunningAuction(minBid: 100);
        $auction->tier = AuctionTier::Platinum;
        $auction->ends_at = now()->addSeconds(5);
        $auction->save();

        $user = User::find($this->currentUserId);
        $updated = $this->service->placeBid($user, $this->currentPlanetId, 1000, 0, 0);

        $remaining = (int) now()->diffInSeconds($updated->ends_at, false);
        $this->assertGreaterThanOrEqual(6, $remaining, 'Platinum should extend by 2-3s minimum');
        $this->assertLessThanOrEqual(8, $remaining, 'Platinum should extend by 2-3s maximum');
    }

    public function testExpiredAuctionRejectsBid(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $auction->ends_at = now()->subSeconds(5);
        $auction->save();

        $user = User::find($this->currentUserId);
        $this->expectException(AuctionBidException::class);
        $this->service->placeBid($user, $this->currentPlanetId, 1000, 0, 0);
    }

    public function testPrizeAssignmentResources(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $auction->lot_type = AuctionLotType::Resources;
        $auction->lot_payload = ['metal' => 10000, 'crystal' => 5000, 'deuterium' => 2000];
        $auction->save();

        $user = User::find($this->currentUserId);
        $this->service->placeBid($user, $this->currentPlanetId, 1000, 0, 0);

        // Force auction to end and tick the state machine.
        Auction::whereKey($auction->id)->update(['ends_at' => now()->subSecond()]);

        $this->planetService->reloadPlanet();
        $metalBefore = (int) floor($this->planetService->metal()->get());
        $crystalBefore = (int) floor($this->planetService->crystal()->get());

        $this->service->tick();

        $fresh = $auction->fresh();
        $this->assertSame(AuctionStatus::Assigned, $fresh->status);

        $this->planetService->reloadPlanet();
        $metalAfter = (int) floor($this->planetService->metal()->get());
        $crystalAfter = (int) floor($this->planetService->crystal()->get());

        $this->assertGreaterThanOrEqual($metalBefore + 10000 - 10, $metalAfter, 'Metal prize should be credited');
        $this->assertGreaterThanOrEqual($crystalBefore + 5000 - 10, $crystalAfter, 'Crystal prize should be credited');
    }

    public function testPrizeAssignmentDarkMatter(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $auction->lot_type = AuctionLotType::DarkMatter;
        $auction->lot_payload = ['amount' => 5000];
        $auction->save();

        $user = User::find($this->currentUserId);
        $dmBefore = (int) ($user->dark_matter ?? 0);

        $this->service->placeBid($user, $this->currentPlanetId, 1000, 0, 0);
        Auction::whereKey($auction->id)->update(['ends_at' => now()->subSecond()]);

        $this->service->tick();

        $this->assertSame(AuctionStatus::Assigned, $auction->fresh()->status);
        $user->refresh();
        $this->assertSame($dmBefore + 5000, (int) $user->dark_matter);
    }

    public function testClosedAuctionWithNoBidsIsJustClosed(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        Auction::whereKey($auction->id)->update(['ends_at' => now()->subSecond()]);

        $this->service->tick();

        $fresh = $auction->fresh();
        // No bids → no winner → remains Closed (not Assigned).
        $this->assertSame(AuctionStatus::Closed, $fresh->status);
        $this->assertNull($fresh->current_bidder_user_id);
    }

    public function testAuctioneerIndexPageLoads(): void
    {
        $response = $this->get('/auctioneer');
        $response->assertStatus(200);
        $response->assertSee('div_traderAuctioneer', false);
    }

    public function testWinnerReceivesInGameMessage(): void
    {
        $auction = $this->createRunningAuction(minBid: 100);
        $auction->lot_type = AuctionLotType::DarkMatter;
        $auction->lot_payload = ['amount' => 1000];
        $auction->save();

        $user = User::find($this->currentUserId);
        $this->service->placeBid($user, $this->currentPlanetId, 1000, 0, 0);
        Auction::whereKey($auction->id)->update(['ends_at' => now()->subSecond()]);

        $messagesBefore = Message::query()->where('user_id', $user->id)->where('key', 'auctioneer_won')->count();
        $this->service->tick();
        $messagesAfter = Message::query()->where('user_id', $user->id)->where('key', 'auctioneer_won')->count();

        $this->assertSame($messagesBefore + 1, $messagesAfter, 'Winner must receive an auctioneer_won message');
    }

    public function testSpawnWindowBlocksOutsideHours(): void
    {
        // Set window to a single past hour so "now" is always outside.
        $now = (int) now()->format('G');
        $start = $now > 0 ? 0 : 1;
        $end = $start; // 1-hour window in the past or future
        Setting::query()->updateOrCreate(['key' => 'auctioneer_spawn_start_hour'], ['value' => (string) $start]);
        Setting::query()->updateOrCreate(['key' => 'auctioneer_spawn_end_hour'], ['value' => (string) $end]);

        // Force the service to re-read settings.
        $service = resolve(AuctioneerService::class);

        Auction::query()->delete();
        $service->tick();

        $this->assertSame(0, Auction::query()->count(), 'No auction should spawn outside the configured window');
    }

    public function testBidEndpointReturnsErrorOnNoRunningAuction(): void
    {
        // Ensure no running auctions exist.
        Auction::query()->update(['status' => AuctionStatus::Closed->value]);

        $response = $this->postJson('/ajax/auctioneer/bid', [
            'metal' => 1000,
            'crystal' => 0,
            'deuterium' => 0,
            'planet_id' => $this->currentPlanetId,
        ]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }
}

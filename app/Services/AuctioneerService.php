<?php

namespace OGame\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OGame\Enums\AuctionLotType;
use OGame\Enums\AuctionStatus;
use OGame\Enums\AuctionTier;
use OGame\Enums\DarkMatterTransactionType;
use OGame\Exceptions\AuctionBidException;
use OGame\Factories\PlanetServiceFactory;
use OGame\Models\Auction;
use OGame\Models\AuctionBid;
use OGame\Models\AuctionLotTemplate;
use OGame\Models\Planet;
use OGame\Models\Resources;
use OGame\Models\Setting;
use OGame\Models\User;

class AuctioneerService
{
    /** @var array<string,string>|null */
    private ?array $settingsCache = null;

    public function __construct(
        private readonly PlanetServiceFactory $planetServiceFactory,
        private readonly DarkMatterService $darkMatterService,
    ) {
    }

    // --- Settings -----------------------------------------------------------

    /**
     * @return array<string,string>
     */
    private function settings(): array
    {
        if ($this->settingsCache === null) {
            $this->settingsCache = Setting::query()
                ->where('key', 'like', 'auctioneer_%')
                ->pluck('value', 'key')
                ->all();
        }
        return $this->settingsCache;
    }

    private function setting(string $key, string|int|float $default): string
    {
        return $this->settings()[$key] ?? (string) $default;
    }

    public function isEnabled(): bool
    {
        return $this->setting('auctioneer_enabled', '1') === '1';
    }

    // --- Point calculation --------------------------------------------------

    public function calculatePoints(int $metal, int $crystal, int $deuterium, int $honor = 0): int
    {
        $rateM = (float) $this->setting('auctioneer_point_rate_metal', 1);
        $rateC = (float) $this->setting('auctioneer_point_rate_crystal', 1.5);
        $rateD = (float) $this->setting('auctioneer_point_rate_deuterium', 3);
        $rateH = (float) $this->setting('auctioneer_point_rate_honor', 100);

        return (int) floor($metal * $rateM + $crystal * $rateC + $deuterium * $rateD + $honor * $rateH);
    }

    // --- Query --------------------------------------------------------------

    public function getCurrentAuction(): ?Auction
    {
        return Auction::query()
            ->whereIn('status', [AuctionStatus::Waiting->value, AuctionStatus::Running->value])
            ->orderByDesc('id')
            ->first();
    }

    // --- Bid (hot path) -----------------------------------------------------

    /**
     * Place a bid atomically. Resources are deducted and NOT refunded (OGame rules).
     *
     * @throws AuctionBidException
     */
    public function placeBid(User $user, int $planetId, int $metal, int $crystal, int $deuterium, int $honor = 0): Auction
    {
        if ($metal < 0 || $crystal < 0 || $deuterium < 0 || $honor < 0) {
            throw new AuctionBidException('Invalid amounts');
        }
        if ($metal === 0 && $crystal === 0 && $deuterium === 0 && $honor === 0) {
            throw new AuctionBidException('Empty bid');
        }

        $isHonorBid = $honor > 0 && $metal === 0 && $crystal === 0 && $deuterium === 0;

        $planetService = null;
        if (!$isHonorBid) {
            $planetService = $this->planetServiceFactory->make($planetId, true);
            if ($planetService === null) {
                throw new AuctionBidException('Invalid planet');
            }
            $planetRow = Planet::find($planetId);
            if ($planetRow === null || (int) $planetRow->user_id !== (int) $user->id) {
                throw new AuctionBidException('Planet does not belong to user');
            }
        }

        $points = $this->calculatePoints($metal, $crystal, $deuterium, $honor);
        $minIncrement = (int) $this->setting('auctioneer_min_increment_points', 1000);
        $extThreshold = (int) $this->setting('auctioneer_extension_threshold_seconds', 30);
        $extMin = (int) $this->setting('auctioneer_extension_min_seconds', 10);
        $extMax = (int) $this->setting('auctioneer_extension_max_seconds', 25);

        return DB::transaction(function () use ($user, $planetId, $planetService, $isHonorBid, $metal, $crystal, $deuterium, $honor, $points, $minIncrement, $extThreshold, $extMin, $extMax) {
            // Lock the running auction
            /** @var Auction|null $auction */
            $auction = Auction::query()
                ->where('status', AuctionStatus::Running->value)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($auction === null) {
                throw new AuctionBidException('No running auction');
            }
            if ($auction->ends_at === null || $auction->ends_at->isPast()) {
                throw new AuctionBidException('Auction already ended');
            }

            // Validate minimum
            $required = max((int) $auction->min_bid_points, (int) $auction->current_bid_points + $minIncrement);
            if ($points < $required) {
                throw new AuctionBidException("Bid too low. Required: {$required} points, got: {$points}");
            }

            if ($isHonorBid) {
                // Atomic honor_points deduction with row lock
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->first();
                if ($lockedUser === null || (int) $lockedUser->honor_points < $honor) {
                    throw new AuctionBidException('Insufficient honor points');
                }
                $lockedUser->honor_points = (int) $lockedUser->honor_points - $honor;
                $lockedUser->save();
            } else {
                $cost = new Resources($metal, $crystal, $deuterium, 0);
                if (!$planetService->deductResourcesAtomic($cost)) {
                    throw new AuctionBidException('Insufficient resources');
                }
            }

            // Record bid log
            $bid = new AuctionBid();
            $bid->auction_id = $auction->id;
            $bid->user_id = $user->id;
            $bid->planet_id = $isHonorBid ? null : $planetId;
            $bid->metal = $metal;
            $bid->crystal = $crystal;
            $bid->deuterium = $deuterium;
            $bid->honor = $honor;
            $bid->points = $points;
            $bid->total_points_after = $points;
            $bid->placed_at = now();
            $bid->save();

            // Update auction state
            $auction->current_bid_points = $points;
            $auction->current_bidder_user_id = $user->id;
            $auction->current_bidder_planet_id = $isHonorBid ? null : $planetId;
            $auction->current_bidder_name = $user->username ?? ('Player#' . $user->id);
            $auction->bid_count = (int) $auction->bid_count + 1;

            // Late bid → extend timer (random in [extMin..extMax])
            $remaining = (int) now()->diffInSeconds($auction->ends_at, false);
            if ($remaining <= $extThreshold) {
                $extra = random_int($extMin, $extMax);
                $auction->ends_at = $auction->ends_at->copy()->addSeconds($extra);
                $auction->extension_count = (int) $auction->extension_count + 1;
            }

            $auction->save();

            return $auction;
        }, 3);
    }

    // --- State machine tick -------------------------------------------------

    public function tick(): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        DB::transaction(function () {
            // 1. Close running auctions whose timer expired
            /** @var Auction|null $running */
            $running = Auction::query()
                ->where('status', AuctionStatus::Running->value)
                ->where('ends_at', '<=', now())
                ->lockForUpdate()
                ->first();
            if ($running !== null) {
                $this->closeAndAssign($running);
            }

            // 2. Promote waiting auction to running
            /** @var Auction|null $waiting */
            $waiting = Auction::query()
                ->where('status', AuctionStatus::Waiting->value)
                ->where('waiting_ends_at', '<=', now())
                ->lockForUpdate()
                ->first();
            if ($waiting !== null) {
                $this->startAuction($waiting);
            }

            // 3. Spawn next auction (waiting) if none pending or running
            $openExists = Auction::query()
                ->whereIn('status', [AuctionStatus::Waiting->value, AuctionStatus::Running->value])
                ->exists();
            if (!$openExists) {
                $this->spawnNewAuction();
            }
        }, 3);
    }

    private function startAuction(Auction $auction): void
    {
        $duration = (int) $this->setting('auctioneer_duration_seconds', 2700);
        $auction->status = AuctionStatus::Running;
        $auction->started_at = now();
        $auction->ends_at = now()->addSeconds($duration);
        $auction->save();
    }

    private function closeAndAssign(Auction $auction): void
    {
        $auction->status = AuctionStatus::Closed;
        $auction->closed_at = now();

        if ($auction->current_bidder_user_id !== null) {
            try {
                $this->assignPrize($auction);
                $auction->status = AuctionStatus::Assigned;
                $auction->assigned_at = now();
            } catch (\Throwable $e) {
                Log::error('Auctioneer assign failed', [
                    'auction_id' => $auction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $auction->save();
    }

    private function spawnNewAuction(): void
    {
        $template = $this->pickTemplate();
        if ($template === null) {
            return;
        }

        $waiting = (int) $this->setting('auctioneer_waiting_seconds', 3600);

        $auction = new Auction();
        $auction->status = AuctionStatus::Waiting;
        $auction->tier = $template->tier;
        $auction->lot_type = $template->lot_type;
        $auction->lot_payload = $template->lot_payload;
        $auction->lot_title = $template->lot_title;
        $auction->lot_image = $template->lot_image;
        $auction->min_bid_points = $template->min_bid_points;
        $auction->current_bid_points = 0;
        $auction->waiting_ends_at = now()->addSeconds($waiting);
        $auction->save();
    }

    private function pickTemplate(): ?AuctionLotTemplate
    {
        $templates = AuctionLotTemplate::query()->where('enabled', true)->get();
        if ($templates->isEmpty()) {
            return null;
        }

        $totalWeight = (int) $templates->sum('weight');
        if ($totalWeight <= 0) {
            return $templates->random();
        }
        $roll = random_int(1, $totalWeight);
        $acc = 0;
        foreach ($templates as $t) {
            $acc += (int) $t->weight;
            if ($roll <= $acc) {
                return $t;
            }
        }
        return $templates->last();
    }

    // --- Prize assignment ---------------------------------------------------

    private function assignPrize(Auction $auction): void
    {
        $winnerId = (int) $auction->current_bidder_user_id;
        $planetId = (int) ($auction->current_bidder_planet_id ?? 0);

        $user = User::find($winnerId);
        if ($user === null) {
            throw new \RuntimeException('Winner user not found');
        }

        // Honor-only winners have no associated planet — fall back to the user's current planet
        // so ship/resources prizes have somewhere to land.
        if ($planetId === 0) {
            $planetId = (int) ($user->planet_current ?? 0);
            if ($planetId === 0) {
                $firstPlanet = Planet::query()->where('user_id', $user->id)->orderBy('id')->first();
                $planetId = $firstPlanet ? (int) $firstPlanet->id : 0;
            }
        }

        $payload = $auction->lot_payload ?? [];

        switch ($auction->lot_type) {
            case AuctionLotType::Resources:
                $this->grantResources($planetId, (int) ($payload['metal'] ?? 0), (int) ($payload['crystal'] ?? 0), (int) ($payload['deuterium'] ?? 0));
                break;

            case AuctionLotType::Ship:
                $this->grantShip($planetId, (int) ($payload['unit_id'] ?? 0), (int) ($payload['amount'] ?? 0));
                break;

            case AuctionLotType::DarkMatter:
                $amount = (int) ($payload['amount'] ?? 0);
                if ($amount > 0) {
                    $this->darkMatterService->credit(
                        $user,
                        $amount,
                        DarkMatterTransactionType::AUCTIONEER->value,
                        'Auctioneer prize (auction #' . $auction->id . ')'
                    );
                }
                break;

            case AuctionLotType::BoosterKraken:
            case AuctionLotType::BoosterDetroid:
            case AuctionLotType::BoosterNewtron:
            case AuctionLotType::ResourceBoost:
                // Booster system not yet implemented; log for manual handling.
                Log::info('Auctioneer booster awarded (pending booster system)', [
                    'auction_id' => $auction->id,
                    'user_id' => $winnerId,
                    'lot_type' => $auction->lot_type->value,
                    'payload' => $payload,
                ]);
                break;
        }
    }

    private function grantResources(int $planetId, int $metal, int $crystal, int $deuterium): void
    {
        $service = $this->planetServiceFactory->make($planetId, true);
        if ($service === null) {
            return;
        }
        $service->addResources(new Resources($metal, $crystal, $deuterium, 0), true);
    }

    private function grantShip(int $planetId, int $unitId, int $amount): void
    {
        if ($unitId <= 0 || $amount <= 0) {
            return;
        }
        $service = $this->planetServiceFactory->make($planetId, true);
        if ($service === null) {
            return;
        }
        try {
            $object = \OGame\Services\ObjectService::getUnitObjectById($unitId);
        } catch (\Throwable $e) {
            Log::warning('Auctioneer ship grant: invalid unit id', ['unit_id' => $unitId]);
            return;
        }
        $service->addUnit($object->machine_name, $amount, true);
    }
}

<?php

namespace OGame\Services;




use RuntimeException;
use Throwable;
use OGame\Services\ObjectService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OGame\Enums\AuctionLotType;
use OGame\Enums\AuctionStatus;
use OGame\Enums\AuctionTier;
use OGame\Enums\DarkMatterTransactionType;
use OGame\Exceptions\AuctionBidException;
use OGame\Factories\PlanetServiceFactory;
use OGame\Factories\PlayerServiceFactory;
use OGame\GameMessages\AuctioneerWon;
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
    private array|null $settingsCache = null;

    public function __construct(
        private readonly PlanetServiceFactory $planetServiceFactory,
        private readonly PlayerServiceFactory $playerServiceFactory,
        private readonly DarkMatterService $darkMatterService,
        private readonly InventoryService $inventoryService,
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

    public function getCurrentAuction(): Auction|null
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

        return DB::transaction(function () use ($user, $planetId, $planetService, $isHonorBid, $metal, $crystal, $deuterium, $honor, $points, $minIncrement, $extThreshold) {
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

            // Late bid → extend timer using tier-specific window.
            // Source: ogame-ninja/auction.go observations of OGame live behavior.
            $remaining = (int) now()->diffInSeconds($auction->ends_at, false);
            if ($remaining <= $extThreshold) {
                [$min, $max] = $auction->tier->lateBidExtensionRange();
                $extra = random_int($min, $max);
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

    // --- Developer / admin shortcuts ---------------------------------------
    //
    // These helpers let admins skip the natural Waiting/Running countdown for
    // local testing. They mutate timestamps and re-run the state machine so
    // the rest of the system (assignPrize, locks, transactions) stays honest.

    /**
     * Force-end the currently running auction immediately.
     * If a bidder exists, the prize is assigned via the normal flow.
     * Returns the auction id that was closed, or null if nothing was running.
     */
    public function devForceEndCurrent(): int|null
    {
        return DB::transaction(function () {
            /** @var Auction|null $auction */
            $auction = Auction::query()
                ->whereIn('status', [AuctionStatus::Running->value, AuctionStatus::Waiting->value])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($auction === null) {
                return null;
            }
            // Waiting auctions have never opened for bids → force-start first so the
            // close flow can run uniformly (timestamps set, state machine honest).
            if ($auction->status === AuctionStatus::Waiting) {
                $this->startAuction($auction);
            }
            $auction->ends_at = now()->subSecond();
            $auction->save();
            $this->closeAndAssign($auction);
            return (int) $auction->id;
        }, 3);
    }

    /**
     * Force-promote the current waiting auction to Running immediately.
     * Returns the auction id promoted, or null if nothing was waiting.
     */
    public function devForceStartWaiting(): int|null
    {
        return DB::transaction(function () {
            /** @var Auction|null $waiting */
            $waiting = Auction::query()
                ->where('status', AuctionStatus::Waiting->value)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($waiting === null) {
                return null;
            }
            $this->startAuction($waiting);
            return (int) $waiting->id;
        }, 3);
    }

    /**
     * Spawn a new Waiting auction immediately, regardless of whether one
     * already exists. Returns the new auction id, or null if no template is
     * available.
     */
    public function devSpawnAuction(): int|null
    {
        return DB::transaction(function () {
            $before = Auction::query()->max('id');
            $this->spawnNewAuction();
            $after = Auction::query()->max('id');
            return ($after !== null && $after !== $before) ? (int) $after : null;
        }, 3);
    }

    /**
     * Spawn a new Waiting auction for a specific lot template (by id).
     * Ignores weight/enabled flag so any template can be tested directly.
     * Returns the new auction id, or null if the template does not exist.
     */
    public function devSpawnAuctionForTemplate(int $templateId): int|null
    {
        return DB::transaction(function () use ($templateId) {
            $template = AuctionLotTemplate::find($templateId);
            if ($template === null) {
                return null;
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
            return (int) $auction->id;
        }, 3);
    }

    /**
     * Cancel the current Waiting/Running auction without delivering any
     * prize. Useful to clean up a stuck test auction. Resources already
     * spent on bids are NOT refunded (matches OGame rules).
     * Returns the cancelled auction id, or null if none open.
     */
    public function devCancelCurrent(): int|null
    {
        return DB::transaction(function () {
            /** @var Auction|null $auction */
            $auction = Auction::query()
                ->whereIn('status', [AuctionStatus::Waiting->value, AuctionStatus::Running->value])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
            if ($auction === null) {
                return null;
            }
            $auction->status = AuctionStatus::Cancelled;
            $auction->closed_at = now();
            $auction->save();
            return (int) $auction->id;
        }, 3);
    }

    private function startAuction(Auction $auction): void
    {
        $duration = (int) $this->setting('auctioneer_duration_seconds', 2700);
        // Per-tier jitter: real OGame shows only "approximate time" to discourage
        // pure sniping. The exact close time is randomized within the tier window.
        [$min, $max] = $auction->tier->lateBidExtensionRange();
        $jitter = random_int($min, $max);
        $auction->status = AuctionStatus::Running;
        $auction->started_at = now();
        $auction->ends_at = now()->addSeconds($duration + $jitter);
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
                $this->sendWinnerMessage($auction);
            } catch (Throwable $e) {
                // Log with full context then re-throw to rollback the outer
                // DB::transaction in tick(). The auction stays Running and
                // the next tick retries — this avoids silently losing prizes
                // when transient failures occur (DB glitch, service down).
                Log::error('Auctioneer assign failed — rolling back, will retry on next tick', [
                    'auction_id' => $auction->id,
                    'winner_user_id' => $auction->current_bidder_user_id,
                    'winner_planet_id' => $auction->current_bidder_planet_id,
                    'lot_type' => $auction->lot_type->value,
                    'lot_payload' => $auction->lot_payload,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        }

        $auction->save();
    }

    private function spawnNewAuction(): void
    {
        // OGame spawns one auction per hour from 06:00 to 23:00 (board.it Macro Guida).
        // Outside this window, no new auction is spawned. Configurable via settings
        // to support 24/7 testing or different server policies.
        $startHour = (int) $this->setting('auctioneer_spawn_start_hour', 6);
        $endHour = (int) $this->setting('auctioneer_spawn_end_hour', 23);
        if ($startHour >= 0 && $endHour >= 0 && $startHour <= $endHour) {
            $hour = (int) now()->format('G');
            if ($hour < $startHour || $hour > $endHour) {
                return;
            }
        }

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

    private function pickTemplate(): AuctionLotTemplate|null
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

    private function sendWinnerMessage(Auction $auction): void
    {
        $winnerId = (int) $auction->current_bidder_user_id;
        if ($winnerId <= 0) {
            return;
        }
        try {
            $playerService = $this->playerServiceFactory->make($winnerId);
            if ($playerService->getId() <= 0) {
                return;
            }
            $planetId = (int) ($auction->current_bidder_planet_id ?? 0);
            if ($planetId <= 0) {
                $planetId = (int) ($playerService->getUser()->planet_current ?? 0);
            }
            $planetTag = $planetId > 0 ? '[planet]' . $planetId . '[/planet]' : '-';

            $messageService = resolve(MessageService::class, ['player' => $playerService]);
            $messageService->sendSystemMessageToPlayer($playerService, AuctioneerWon::class, [
                'lot_title' => (string) $auction->lot_title,
                'planet' => $planetTag,
                'bid_points' => (string) $auction->current_bid_points,
            ]);
        } catch (Throwable $e) {
            Log::warning('Auctioneer winner message not sent', [
                'auction_id' => $auction->id,
                'winner_user_id' => $winnerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function assignPrize(Auction $auction): void
    {
        $winnerId = (int) $auction->current_bidder_user_id;
        $planetId = (int) ($auction->current_bidder_planet_id ?? 0);

        $user = User::find($winnerId);
        if ($user === null) {
            throw new RuntimeException('Winner user not found');
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
                $this->grantResources($planetId, $winnerId, (int) ($payload['metal'] ?? 0), (int) ($payload['crystal'] ?? 0), (int) ($payload['deuterium'] ?? 0));
                break;

            case AuctionLotType::Ship:
                $this->grantShip($planetId, $winnerId, (int) ($payload['unit_id'] ?? 0), (int) ($payload['amount'] ?? 0));
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
                $granted = $this->inventoryService->grantFromAuction($user, $auction);
                if ($granted === null) {
                    Log::warning('Auctioneer booster/amplifier not granted to inventory', [
                        'auction_id' => $auction->id,
                        'user_id' => $winnerId,
                        'lot_type' => $auction->lot_type->value,
                    ]);
                }
                break;
        }
    }

    /**
     * Resolve a deliverable planet service for the given winner. Falls back to
     * the user's current/first planet if the original planet is gone.
     * Throws if nothing usable is found (caller rolls back transaction).
     */
    private function resolvePlanetServiceForWinner(int $planetId, int $winnerUserId): PlanetService
    {
        $service = $planetId > 0 ? $this->planetServiceFactory->make($planetId, true) : null;
        if ($service !== null) {
            return $service;
        }

        $user = User::find($winnerUserId);
        if ($user !== null) {
            $fallbackId = (int) ($user->planet_current ?? 0);
            if ($fallbackId > 0) {
                $service = $this->planetServiceFactory->make($fallbackId, true);
                if ($service !== null) {
                    Log::warning('Auctioneer prize delivery: primary planet missing, using planet_current', [
                        'user_id' => $winnerUserId,
                        'requested_planet_id' => $planetId,
                        'fallback_planet_id' => $fallbackId,
                    ]);
                    return $service;
                }
            }
            $firstPlanet = Planet::query()->where('user_id', $winnerUserId)->orderBy('id')->first();
            if ($firstPlanet !== null) {
                $service = $this->planetServiceFactory->make((int) $firstPlanet->id, true);
                if ($service !== null) {
                    Log::warning('Auctioneer prize delivery: using first available planet as fallback', [
                        'user_id' => $winnerUserId,
                        'requested_planet_id' => $planetId,
                        'fallback_planet_id' => (int) $firstPlanet->id,
                    ]);
                    return $service;
                }
            }
        }

        throw new RuntimeException(
            "Auctioneer prize undeliverable: no planet found for user {$winnerUserId} (requested planet {$planetId})"
        );
    }

    private function grantResources(int $planetId, int $winnerUserId, int $metal, int $crystal, int $deuterium): void
    {
        $service = $this->resolvePlanetServiceForWinner($planetId, $winnerUserId);
        $service->addResources(new Resources($metal, $crystal, $deuterium, 0), true);
    }

    private function grantShip(int $planetId, int $winnerUserId, int $unitId, int $amount): void
    {
        if ($unitId <= 0 || $amount <= 0) {
            Log::error('Auctioneer ship grant: invalid payload', [
                'unit_id' => $unitId,
                'amount' => $amount,
                'planet_id' => $planetId,
                'user_id' => $winnerUserId,
            ]);
            throw new RuntimeException("Invalid ship prize payload (unit_id={$unitId}, amount={$amount})");
        }
        try {
            $object = ObjectService::getUnitObjectById($unitId);
        } catch (Throwable $e) {
            Log::error('Auctioneer ship grant: invalid unit id', [
                'unit_id' => $unitId,
                'user_id' => $winnerUserId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
        $service = $this->resolvePlanetServiceForWinner($planetId, $winnerUserId);
        $service->addUnit($object->machine_name, $amount, true);
    }
}

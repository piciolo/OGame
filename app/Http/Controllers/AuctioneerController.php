<?php

namespace OGame\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use OGame\Enums\AuctionStatus;
use OGame\Exceptions\AuctionBidException;
use OGame\Models\Auction;
use OGame\Services\AuctioneerService;
use OGame\Services\ObjectService;
use OGame\Services\PlayerService;

class AuctioneerController extends OGameController
{
    public function __construct(private readonly AuctioneerService $auctioneer)
    {
    }

    public function index(PlayerService $player): View
    {
        $this->setBodyId('traderOverview');

        return view('ingame.auctioneer.index', $this->buildViewData($player));
    }

    public function partial(PlayerService $player): View
    {
        return view('ingame.auctioneer.partial', $this->buildViewData($player));
    }

    /**
     * @return array<string,mixed>
     */
    private function buildViewData(PlayerService $player): array
    {
        $auction = $this->auctioneer->getCurrentAuction();
        if ($auction !== null && empty($auction->lot_image)) {
            $auction->lot_image = $this->deriveLotImage($auction->lot_type->value, (array) $auction->lot_payload);
        }
        $history = $this->fetchHistory();
        $planets = $player->planets->all();

        return [
            'auction' => $auction,
            'history' => $history,
            'planets' => $planets,
            'currentPlanetId' => $player->planets->current()->getPlanetId(),
            'serverNow' => now()->timestamp,
            'rates' => [
                'metal' => (float) ($this->settingValue('auctioneer_point_rate_metal', 1)),
                'crystal' => (float) ($this->settingValue('auctioneer_point_rate_crystal', 1.5)),
                'deuterium' => (float) ($this->settingValue('auctioneer_point_rate_deuterium', 3)),
                'honor' => (float) ($this->settingValue('auctioneer_point_rate_honor', 100)),
            ],
            'minIncrement' => (int) $this->settingValue('auctioneer_min_increment_points', 1),
            'honorPoints' => (int) ($player->getUser()->honor_points ?? 0),
        ];
    }

    public function status(): JsonResponse
    {
        $auction = $this->auctioneer->getCurrentAuction();
        return response()->json($this->serializeAuction($auction));
    }

    public function bid(Request $request, PlayerService $player): JsonResponse
    {
        $metal = max(0, (int) str_replace([',', ' ', '.'], '', (string) $request->input('metal', 0)));
        $crystal = max(0, (int) str_replace([',', ' ', '.'], '', (string) $request->input('crystal', 0)));
        $deuterium = max(0, (int) str_replace([',', ' ', '.'], '', (string) $request->input('deuterium', 0)));
        $honor = max(0, (int) str_replace([',', ' ', '.'], '', (string) $request->input('honor', 0)));
        $planetId = (int) $request->input('planet_id', 0);

        try {
            $auction = $this->auctioneer->placeBid(
                $player->getUser(),
                $planetId,
                $metal,
                $crystal,
                $deuterium,
                $honor,
            );
        } catch (AuctionBidException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'auction' => $this->serializeAuction($auction),
        ]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetchHistory(): array
    {
        $limit = (int) $this->settingValue('auctioneer_history_size', 20);

        return Auction::query()
            ->whereIn('status', [AuctionStatus::Closed->value, AuctionStatus::Assigned->value, AuctionStatus::Cancelled->value])
            ->orderByDesc('closed_at')
            ->limit($limit)
            ->get()
            ->map(fn (Auction $a) => [
                'id' => $a->id,
                'tier' => $a->tier->value,
                'lot_title' => $a->lot_title,
                'lot_image' => !empty($a->lot_image) ? $a->lot_image : $this->deriveLotImage($a->lot_type->value, (array) $a->lot_payload),
                'winning_bid' => (int) $a->current_bid_points,
                'winner_name' => $a->current_bidder_name,
                'winner_user_id' => $a->current_bidder_user_id,
                'closed_at' => $a->closed_at?->format('H:i:s'),
                'sold' => $a->current_bidder_user_id !== null,
            ])
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeAuction(?Auction $auction): array
    {
        if ($auction === null) {
            return ['status' => 'none'];
        }

        return [
            'id' => $auction->id,
            'status' => $auction->status->value,
            'tier' => $auction->tier->value,
            'lot_type' => $auction->lot_type->value,
            'lot_title' => $auction->lot_title,
            'lot_image' => $auction->lot_image,
            'lot_payload' => $auction->lot_payload,
            'current_bid_points' => (int) $auction->current_bid_points,
            'min_bid_points' => (int) $auction->min_bid_points,
            'bid_count' => (int) $auction->bid_count,
            'current_bidder_name' => $auction->current_bidder_name,
            'current_bidder_user_id' => $auction->current_bidder_user_id,
            'ends_at' => $auction->ends_at?->timestamp,
            'waiting_ends_at' => $auction->waiting_ends_at?->timestamp,
            'server_now' => now()->timestamp,
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function deriveLotImage(string $lotType, array $payload): string
    {
        if ($lotType === 'ship') {
            $unitId = (int) ($payload['unit_id'] ?? 0);
            if ($unitId > 0) {
                try {
                    $obj = resolve(ObjectService::class)->getObjectById($unitId);
                    $path = public_path('img/objects/units/' . $obj->machine_name . '_small.jpg');
                    if (is_file($path)) {
                        return '/img/objects/units/' . $obj->machine_name . '_small.jpg';
                    }
                } catch (\Throwable) {}
            }
            return '/img/objects/units/cruiser_small.jpg';
        }
        if (str_starts_with($lotType, 'booster_')) {
            return '/img/objects/buildings/alliance_depot_small.jpg';
        }
        if ($lotType === 'dark_matter') {
            return '/img/objects/research/astrophysics_technology_small.jpg';
        }
        if ($lotType === 'resources') {
            return '/img/objects/buildings/metal_mine_small.jpg';
        }
        return '/img/objects/units/cruiser_small.jpg';
    }

    private function settingValue(string $key, string|int|float $default): string
    {
        static $cache = null;
        if ($cache === null) {
            $cache = \OGame\Models\Setting::query()
                ->where('key', 'like', 'auctioneer_%')
                ->pluck('value', 'key')
                ->all();
        }
        return $cache[$key] ?? (string) $default;
    }
}

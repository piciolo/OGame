<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\AuctionLotTemplate;

/**
 * Aligned with official OGame Gameforge Auctioneer catalog.
 * Source: direct capture from https://s272-it.ogame.gameforge.com (Quasi-Stellar IT, 2026-04-22)
 * + wiki.ogame.org + board.en.ogame.gameforge.com
 * See _research/auctioneer/official_lots_capture.md
 *
 * Catalog: 7 item families × 4 tiers = 28 lots.
 * - metal/crystal/deuterium boosters — 7-day buff, +10/+20/+30/+40%
 * - energy booster — 7-day buff, +20/+40/+60/+80% (confirmed live capture 2026-04-23)
 * - 3 time-reduction boosters (KRAKEN/NEWTRON/DETROID) — single use, -30m/-2h/-6h/-24h
 */
class AuctionLotTemplatesSeeder extends Seeder
{
    private const WEEK_SECONDS = 604800;

    private const BOOST_PERCENT = [
        'bronze' => 10,
        'silver' => 20,
        'gold' => 30,
        'platinum' => 40,
    ];

    private const BOOST_PERCENT_ENERGY = [
        'bronze' => 20,
        'silver' => 40,
        'gold' => 60,
        'platinum' => 80,
    ];

    private const TIME_REDUCTION_SECONDS = [
        'bronze' => 1800,    // 30 minutes
        'silver' => 7200,    // 2 hours
        'gold' => 21600,     // 6 hours
        'platinum' => 86400, // 24 hours
    ];

    private const MIN_BID_POINTS = [
        'bronze' => 1000,
        'silver' => 5000,
        'gold' => 20000,
        'platinum' => 60000,
    ];

    /**
     * Dark Matter price for direct buy (from official shop, 7-day amplifiers).
     * Bronze and time-reduction boosters are NOT buyable via DM — obtainable only by winning the auction.
     * Source: https://s272-it.ogame.gameforge.com shop catalog (Risorse, scraped 2026-04-23).
     */
    private const DM_PRICE_AMPLIFIER = [
        'bronze' => null,
        'silver' => 7500,
        'gold' => 20000,
        'platinum' => 30000,
    ];

    private const WEIGHT_PER_TIER = [
        'bronze' => 30,
        'silver' => 15,
        'gold' => 8,
        'platinum' => 3,
    ];

    public function run(): void
    {
        $templates = [];

        // ---- Resource production amplifiers (7-day buff) ----
        $resources = [
            'metal' => 'Amplificatore di metallo',
            'crystal' => 'Amplificatore di cristallo',
            'deuterium' => 'Amplificatore di deuterio',
            'energy' => 'Amplificatore di energia',
        ];

        foreach ($resources as $resKey => $resLabelIt) {
            $percents = $resKey === 'energy' ? self::BOOST_PERCENT_ENERGY : self::BOOST_PERCENT;
            foreach ($percents as $tier => $percent) {
                $templates[] = [
                    'name' => ucfirst($resKey) . ' Booster ' . ucfirst($tier),
                    'tier' => $tier,
                    'lot_type' => 'resource_boost',
                    'lot_title' => $resLabelIt . ' ' . $this->tierNameIt($tier),
                    'lot_payload' => [
                        'resource' => $resKey,
                        'percent' => $percent,
                        'duration_seconds' => self::WEEK_SECONDS,
                    ],
                    'weight' => self::WEIGHT_PER_TIER[$tier],
                    'min_bid_points' => self::MIN_BID_POINTS[$tier],
                    'dm_price' => self::DM_PRICE_AMPLIFIER[$tier],
                ];
            }
        }

        // ---- Time-reduction boosters (single use) ----
        $timeBoosters = [
            'booster_kraken' => 'KRAKEN',
            'booster_newtron' => 'NEWTRON',
            'booster_detroid' => 'DETROID',
        ];

        foreach ($timeBoosters as $lotType => $boosterName) {
            foreach (self::TIME_REDUCTION_SECONDS as $tier => $reductionSeconds) {
                $templates[] = [
                    'name' => $boosterName . ' ' . ucfirst($tier),
                    'tier' => $tier,
                    'lot_type' => $lotType,
                    'lot_title' => $boosterName . ' ' . $this->tierNameIt($tier),
                    'lot_payload' => [
                        'duration_seconds' => $reductionSeconds,
                    ],
                    'weight' => self::WEIGHT_PER_TIER[$tier],
                    'min_bid_points' => self::MIN_BID_POINTS[$tier],
                    'dm_price' => null,
                ];
            }
        }

        $validNames = array_column($templates, 'name');

        // Disable any template not in the official 28-lot catalog.
        AuctionLotTemplate::whereNotIn('name', $validNames)->update(['enabled' => false]);

        foreach ($templates as $t) {
            AuctionLotTemplate::updateOrCreate(
                ['name' => $t['name']],
                array_merge($t, ['enabled' => true])
            );
        }
    }

    private function tierNameIt(string $tier): string
    {
        return match ($tier) {
            'bronze' => 'Bronzo',
            'silver' => 'Argento',
            'gold' => 'Oro',
            'platinum' => 'Platino',
            default => ucfirst($tier),
        };
    }
}

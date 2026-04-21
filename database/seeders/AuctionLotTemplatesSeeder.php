<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\AuctionLotTemplate;

class AuctionLotTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // BRONZE — basic resources / small ships
            ['name' => 'Bronze Resources S', 'tier' => 'bronze', 'lot_type' => 'resources', 'lot_title' => '200k Metal + 100k Crystal', 'lot_payload' => ['metal' => 200000, 'crystal' => 100000], 'weight' => 30, 'min_bid_points' => 1000],
            ['name' => 'Bronze Light Fighters', 'tier' => 'bronze', 'lot_type' => 'ship', 'lot_title' => '100 Light Fighters', 'lot_payload' => ['unit_id' => 204, 'amount' => 100], 'weight' => 20, 'min_bid_points' => 1000],
            ['name' => 'Bronze Kraken', 'tier' => 'bronze', 'lot_type' => 'booster_kraken', 'lot_title' => 'KRAKEN Bronze (30min build)', 'lot_payload' => ['duration_seconds' => 1800], 'weight' => 10, 'min_bid_points' => 2000],

            // SILVER
            ['name' => 'Silver Resources', 'tier' => 'silver', 'lot_type' => 'resources', 'lot_title' => '500k Metal + 300k Crystal + 100k Deut', 'lot_payload' => ['metal' => 500000, 'crystal' => 300000, 'deuterium' => 100000], 'weight' => 25, 'min_bid_points' => 5000],
            ['name' => 'Silver Cruisers', 'tier' => 'silver', 'lot_type' => 'ship', 'lot_title' => '50 Cruisers', 'lot_payload' => ['unit_id' => 206, 'amount' => 50], 'weight' => 15, 'min_bid_points' => 5000],
            ['name' => 'Silver Detroid', 'tier' => 'silver', 'lot_type' => 'booster_detroid', 'lot_title' => 'DETROID Silver (2h shipyard)', 'lot_payload' => ['duration_seconds' => 7200], 'weight' => 10, 'min_bid_points' => 8000],
            ['name' => 'Silver Newtron', 'tier' => 'silver', 'lot_type' => 'booster_newtron', 'lot_title' => 'NEWTRON Silver (2h research)', 'lot_payload' => ['duration_seconds' => 7200], 'weight' => 10, 'min_bid_points' => 8000],

            // GOLD
            ['name' => 'Gold Resources', 'tier' => 'gold', 'lot_type' => 'resources', 'lot_title' => '2M Metal + 1M Crystal + 500k Deut', 'lot_payload' => ['metal' => 2000000, 'crystal' => 1000000, 'deuterium' => 500000], 'weight' => 20, 'min_bid_points' => 20000],
            ['name' => 'Gold Battleships', 'tier' => 'gold', 'lot_type' => 'ship', 'lot_title' => '30 Battleships', 'lot_payload' => ['unit_id' => 207, 'amount' => 30], 'weight' => 15, 'min_bid_points' => 20000],
            ['name' => 'Gold Kraken 6h', 'tier' => 'gold', 'lot_type' => 'booster_kraken', 'lot_title' => 'KRAKEN Gold (6h build)', 'lot_payload' => ['duration_seconds' => 21600], 'weight' => 10, 'min_bid_points' => 30000],
            ['name' => 'Gold Dark Matter', 'tier' => 'gold', 'lot_type' => 'dark_matter', 'lot_title' => '5000 Dark Matter', 'lot_payload' => ['amount' => 5000], 'weight' => 8, 'min_bid_points' => 30000],

            // PLATINUM
            ['name' => 'Platinum Bombers', 'tier' => 'platinum', 'lot_type' => 'ship', 'lot_title' => '20 Bombers', 'lot_payload' => ['unit_id' => 211, 'amount' => 20], 'weight' => 10, 'min_bid_points' => 100000],
            ['name' => 'Platinum Dark Matter', 'tier' => 'platinum', 'lot_type' => 'dark_matter', 'lot_title' => '20000 Dark Matter', 'lot_payload' => ['amount' => 20000], 'weight' => 10, 'min_bid_points' => 100000],
            ['name' => 'Platinum Resource Boost', 'tier' => 'platinum', 'lot_type' => 'resource_boost', 'lot_title' => 'Metal Booster +30% (7d)', 'lot_payload' => ['resource' => 'metal', 'percent' => 30, 'duration_seconds' => 604800], 'weight' => 8, 'min_bid_points' => 150000],
        ];

        foreach ($templates as $t) {
            AuctionLotTemplate::updateOrCreate(
                ['name' => $t['name']],
                array_merge($t, ['enabled' => true])
            );
        }
    }
}

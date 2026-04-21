<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use OGame\Models\Setting;

class AuctioneerSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'auctioneer_enabled' => '1',
            'auctioneer_duration_seconds' => '2700',
            'auctioneer_waiting_seconds' => '3600',
            'auctioneer_extension_min_seconds' => '10',
            'auctioneer_extension_max_seconds' => '25',
            'auctioneer_extension_threshold_seconds' => '30',
            'auctioneer_early_close_seconds' => '30',
            'auctioneer_min_increment_points' => '1',
            'auctioneer_history_size' => '20',
            'auctioneer_point_rate_metal' => '1',
            'auctioneer_point_rate_crystal' => '1.5',
            'auctioneer_point_rate_deuterium' => '3',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

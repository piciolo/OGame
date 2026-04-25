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
            // Late-bid extension uses tier-specific windows (see AuctionTier::lateBidExtensionRange).
            'auctioneer_extension_threshold_seconds' => '30',
            'auctioneer_early_close_seconds' => '30',
            'auctioneer_min_increment_points' => '1000',
            'auctioneer_history_size' => '20',
            'auctioneer_point_rate_metal' => '1',
            'auctioneer_point_rate_crystal' => '1.5',
            'auctioneer_point_rate_deuterium' => '3',
            'auctioneer_point_rate_honor' => '100',
            // OGame spawns one auction per hour from 06:00 to 23:00.
            'auctioneer_spawn_start_hour' => '6',
            'auctioneer_spawn_end_hour' => '23',
        ];

        foreach ($defaults as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}

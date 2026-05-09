<?php

namespace OGame\Console\Commands\Scheduler;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use OGame\Enums\HighscoreTypeEnum;
use OGame\Models\Highscore;
use OGame\Services\PlayerRankHistoryService;

class SnapshotPlayerRanks extends Command
{
    protected $signature = 'ogamex:scheduler:snapshot-player-ranks';

    protected $description = 'Snapshot daily player ranks for the player profile delta arrows';

    public function handle(PlayerRankHistoryService $rankHistory): void
    {
        $today = Carbon::today();
        $this->info("Snapshotting player ranks for {$today->toDateString()}...");

        $count = 0;
        Highscore::query()->chunkById(500, function ($highscores) use ($rankHistory, $today, &$count) {
            /** @var iterable<Highscore> $highscores */
            foreach ($highscores as $hs) {
                foreach (HighscoreTypeEnum::cases() as $type) {
                    $rankAttr = $type->name.'_rank';
                    $pointsAttr = $type->name;
                    $rank = (int) ($hs->{$rankAttr} ?? 0);
                    $points = (int) ($hs->{$pointsAttr} ?? 0);
                    if ($rank <= 0) {
                        continue;
                    }
                    $rankHistory->recordSnapshot($hs->player_id, $type, $rank, $points, $today);
                    $count++;
                }
            }
        });

        $this->info("Snapshot completed. Rows written: {$count}.");
    }
}

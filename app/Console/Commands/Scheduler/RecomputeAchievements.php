<?php

namespace OGame\Console\Commands\Scheduler;

use Illuminate\Console\Command;
use OGame\Factories\PlayerServiceFactory;
use OGame\Models\User;
use OGame\Services\AchievementProgressTracker;

class RecomputeAchievements extends Command
{
    protected $signature = 'ogamex:scheduler:recompute-achievements';

    protected $description = 'Recompute achievement progress for all players (stateless derive from current state).';

    public function handle(AchievementProgressTracker $tracker, PlayerServiceFactory $playerFactory): void
    {
        $this->info('Recomputing achievements for all players...');
        $count = 0;
        User::query()->select('id', 'username')->chunkById(200, function ($users) use ($tracker, $playerFactory, &$count) {
            foreach ($users as $u) {
                try {
                    $player = $playerFactory->make($u->id);
                    $tracker->recomputeForPlayer($player);
                    $count++;
                } catch (\Throwable $e) {
                    $this->warn('Skip user '.$u->id.': '.$e->getMessage());
                }
            }
        });
        $this->info("Done. Players processed: {$count}.");
    }
}

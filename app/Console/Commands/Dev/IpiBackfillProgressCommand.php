<?php

namespace OGame\Console\Commands\Dev;

use Illuminate\Console\Command;
use OGame\Models\User;
use OGame\Services\IpiProgressService;

/**
 * One-shot recompute of IPI task progress for one user (or all users).
 *
 *   php artisan ogamex:dev:ipi-backfill-progress           # all users
 *   php artisan ogamex:dev:ipi-backfill-progress --user=3  # one user
 *
 * Useful after deploying M3.5 the first time, or to repair drift if hooks
 * miss an event. Idempotent — running multiple times is safe.
 */
class IpiBackfillProgressCommand extends Command
{
    protected $signature = 'ogamex:dev:ipi-backfill-progress
                            {--user= : Limit to a single user_id (default: all users)}';

    protected $description = 'Recompute IPI task progress for one or all users based on current game state.';

    public function handle(IpiProgressService $progressService): int
    {
        $userId = $this->option('user');
        $query = User::query();
        if ($userId) $query->where('id', (int)$userId);

        $users = $query->get(['id', 'username']);
        if ($users->isEmpty()) {
            $this->warn('No users matched.');
            return self::SUCCESS;
        }

        $totalTouched = 0;
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        foreach ($users as $u) {
            $touched = $progressService->recompute($u->id);
            $totalTouched += $touched;
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info(sprintf('Backfill complete: %d users, %d progress rows updated.', $users->count(), $totalTouched));
        return self::SUCCESS;
    }
}

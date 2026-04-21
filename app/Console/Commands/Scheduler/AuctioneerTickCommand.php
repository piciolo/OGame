<?php

namespace OGame\Console\Commands\Scheduler;

use Exception;
use Illuminate\Console\Command;
use OGame\Services\AuctioneerService;

class AuctioneerTickCommand extends Command
{
    protected $signature = 'ogamex:scheduler:auctioneer-tick';

    protected $description = 'Advance auctioneer state machine: spawn, start, close, assign auctions.';

    public function __construct(private readonly AuctioneerService $auctioneerService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $this->auctioneerService->tick();
            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Auctioneer tick failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

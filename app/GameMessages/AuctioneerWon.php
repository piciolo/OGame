<?php

namespace OGame\GameMessages;

use OGame\GameMessages\Abstracts\GameMessage;

class AuctioneerWon extends GameMessage
{
    protected function initialize(): void
    {
        $this->key = 'auctioneer_won';
        $this->params = ['lot_title', 'planet', 'bid_points'];
        $this->tab = 'economy';
        $this->subtab = 'economy';
    }
}

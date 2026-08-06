<?php

namespace OGame\Enums;

enum AuctionStatus: string
{
    case Waiting = 'waiting';
    case Running = 'running';
    case Closed = 'closed';
    case Assigned = 'assigned';
    case Cancelled = 'cancelled';
}

<?php

namespace OGame\Enums;

enum AuctionTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';

    public function weight(): int
    {
        return match ($this) {
            self::Bronze => 50,
            self::Silver => 30,
            self::Gold => 15,
            self::Platinum => 5,
        };
    }
}

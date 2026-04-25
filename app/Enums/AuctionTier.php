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

    /**
     * Late-bid extension window in seconds (min, max). Sourced from
     * ogame-ninja/auction.go observations: smaller tiers extend less
     * to keep premium auctions tighter / harder to snipe.
     *
     * @return array{0:int,1:int}
     */
    public function lateBidExtensionRange(): array
    {
        return match ($this) {
            self::Bronze => [7, 10],
            self::Silver => [5, 7],
            self::Gold => [3, 5],
            self::Platinum => [2, 3],
        };
    }
}

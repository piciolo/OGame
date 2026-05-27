<?php

namespace OGame\Enums;

enum AuctionLotType: string
{
    case Resources = 'resources';
    case Ship = 'ship';
    case BoosterKraken = 'booster_kraken';
    case BoosterDetroid = 'booster_detroid';
    case BoosterNewtron = 'booster_newtron';
    case ResourceBoost = 'resource_boost';
    case DarkMatter = 'dark_matter';
}

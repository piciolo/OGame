<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OGame\Enums\AuctionLotType;
use OGame\Enums\AuctionTier;

/**
 * @property int $id
 * @property string $name
 * @property AuctionTier $tier
 * @property AuctionLotType $lot_type
 * @property array $lot_payload
 * @property string $lot_title
 * @property string|null $lot_image
 * @property int $weight
 * @property int $min_bid_points
 * @property bool $enabled
 */
class AuctionLotTemplate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'tier' => AuctionTier::class,
        'lot_type' => AuctionLotType::class,
        'lot_payload' => 'array',
        'enabled' => 'boolean',
    ];
}

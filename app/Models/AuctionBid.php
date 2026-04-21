<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $auction_id
 * @property int $user_id
 * @property int|null $planet_id
 * @property int $metal
 * @property int $crystal
 * @property int $deuterium
 * @property int $honor
 * @property int $points
 * @property int $total_points_after
 * @property \Illuminate\Support\Carbon $placed_at
 */
class AuctionBid extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'placed_at' => 'datetime',
    ];

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class);
    }
}

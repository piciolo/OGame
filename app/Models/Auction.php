<?php

namespace OGame\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OGame\Enums\AuctionLotType;
use OGame\Enums\AuctionStatus;
use OGame\Enums\AuctionTier;

/**
 * @property int $id
 * @property AuctionStatus $status
 * @property AuctionTier $tier
 * @property AuctionLotType $lot_type
 * @property array $lot_payload
 * @property string $lot_title
 * @property string|null $lot_image
 * @property int $min_bid_points
 * @property int $current_bid_points
 * @property int|null $current_bidder_user_id
 * @property int|null $current_bidder_planet_id
 * @property string|null $current_bidder_name
 * @property Carbon|null $waiting_ends_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $assigned_at
 * @property int $extension_count
 * @property int $bid_count
 */
class Auction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'status' => AuctionStatus::class,
        'tier' => AuctionTier::class,
        'lot_type' => AuctionLotType::class,
        'lot_payload' => 'array',
        'waiting_ends_at' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'closed_at' => 'datetime',
        'assigned_at' => 'datetime',
    ];

    public function bids(): HasMany
    {
        return $this->hasMany(AuctionBid::class);
    }

    public function currentBidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_bidder_user_id');
    }

    public function isBiddable(): bool
    {
        return $this->status === AuctionStatus::Running
            && $this->ends_at !== null
            && $this->ends_at->isFuture();
    }

    public function secondsRemaining(): int
    {
        if ($this->ends_at === null) {
            return 0;
        }
        return max(0, (int) now()->diffInSeconds($this->ends_at, false));
    }
}

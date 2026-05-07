<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $player_id
 * @property int $achievement_id
 * @property int $current_value
 * @property int $completed_tier
 * @property Carbon|null $last_updated_at
 * @property Carbon|null $completed_at
 */
#[Fillable([
    'player_id', 'achievement_id', 'current_value', 'completed_tier',
    'last_updated_at', 'completed_at',
])]
class PlayerAchievementProgress extends Model
{
    protected $table = 'player_achievement_progress';

    protected $casts = [
        'last_updated_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}

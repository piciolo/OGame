<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $achievement_id
 * @property int $tier
 * @property int $target
 * @property string $reward_type     'avatar' | 'skin' | 'title'
 * @property string $reward_machine_name
 */
#[Fillable([
    'achievement_id', 'tier', 'target', 'reward_type', 'reward_machine_name',
])]
class AchievementTier extends Model
{
    public function achievement(): BelongsTo
    {
        return $this->belongsTo(Achievement::class);
    }
}

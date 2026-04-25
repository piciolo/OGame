<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $planet_id
 * @property int $user_id
 * @property string $resource   metal|crystal|deuterium|energy
 * @property int $percent_bonus
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int|null $source_user_item_id
 */
class PlanetBoost extends Model
{
    protected $table = 'planet_boosts';

    protected $fillable = [
        'planet_id',
        'user_id',
        'resource',
        'percent_bonus',
        'expires_at',
        'source_user_item_id',
    ];

    protected $casts = [
        'percent_bonus' => 'int',
        'expires_at' => 'datetime',
    ];

    public function planet(): BelongsTo
    {
        return $this->belongsTo(Planet::class, 'planet_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(UserItem::class, 'source_user_item_id');
    }
}

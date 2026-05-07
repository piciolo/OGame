<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $machine_name
 * @property int $display_number
 * @property string $name_key
 * @property string $description_key
 * @property string $category
 * @property bool $is_secret
 * @property int $sort_order
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AchievementTier> $tiers
 */
#[Fillable([
    'machine_name', 'display_number', 'name_key', 'description_key',
    'category', 'is_secret', 'sort_order',
])]
class Achievement extends Model
{
    protected $casts = [
        'is_secret' => 'boolean',
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(AchievementTier::class)->orderBy('tier');
    }
}

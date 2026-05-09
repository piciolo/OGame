<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $player_id
 * @property string $title_machine_name
 * @property Carbon|null $unlocked_at
 */
#[Fillable(['player_id', 'title_machine_name', 'unlocked_at'])]
#[Table(name: 'player_unlocked_titles')]
class PlayerUnlockedTitle extends Model
{
    protected $casts = [
        'unlocked_at' => 'datetime',
    ];
}

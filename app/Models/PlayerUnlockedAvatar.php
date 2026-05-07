<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $player_id
 * @property string $avatar_machine_name
 * @property \Illuminate\Support\Carbon|null $unlocked_at
 */
#[Fillable(['player_id', 'avatar_machine_name', 'unlocked_at'])]
class PlayerUnlockedAvatar extends Model
{
    protected $table = 'player_unlocked_avatars';

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];
}

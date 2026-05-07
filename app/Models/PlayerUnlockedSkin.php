<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $player_id
 * @property string $skin_machine_name
 * @property \Illuminate\Support\Carbon|null $unlocked_at
 */
#[Fillable(['player_id', 'skin_machine_name', 'unlocked_at'])]
class PlayerUnlockedSkin extends Model
{
    protected $table = 'player_unlocked_skins';

    protected $casts = [
        'unlocked_at' => 'datetime',
    ];
}

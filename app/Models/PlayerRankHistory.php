<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $player_id
 * @property int $highscore_type
 * @property int $rank
 * @property int $points
 * @property Carbon $snapshot_date
 */
#[Fillable([
    'player_id', 'highscore_type', 'rank', 'points', 'snapshot_date',
])]
#[Table(name: 'player_rank_history')]
class PlayerRankHistory extends Model
{

    protected $casts = [
        'snapshot_date' => 'date',
    ];
}

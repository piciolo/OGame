<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $task_id
 * @property string $state             none|tracked|completed|collected
 * @property int $progress_count
 * @property \Illuminate\Support\Carbon|null $tracked_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $collected_at
 */
class IpiPlayerProgress extends Model
{
    protected $table = 'ipi_player_progress';
    protected $guarded = [];
    protected $casts = [
        'tracked_at' => 'datetime',
        'completed_at' => 'datetime',
        'collected_at' => 'datetime',
    ];

    public const STATE_NONE = 'none';
    public const STATE_TRACKED = 'tracked';
    public const STATE_COMPLETED = 'completed';
    public const STATE_COLLECTED = 'collected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(IpiTask::class, 'task_id');
    }
}

<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 * @property int $resource_index  0=metal, 1=crystal, 2=deuterium, 3=energy, 4=dark_matter
 * @property int $quantity
 */
class IpiTaskReward extends Model
{
    protected $guarded = [];

    public function task(): BelongsTo
    {
        return $this->belongsTo(IpiTask::class, 'task_id');
    }
}

<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $task_id
 * @property int $sort_order
 * @property string $text
 * @property string|null $requirement_type
 * @property string|null $requirement_target
 * @property int|null $requirement_value
 * @property string|null $planet_scope
 * @property string|null $hint_target
 */
class IpiTaskAction extends Model
{
    protected $guarded = [];
    protected $casts = [
        'text_translations' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(IpiTask::class, 'task_id');
    }
}

<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $chapter_id
 * @property int $sort_order
 * @property string $title
 * @property string $description
 * @property string $image
 * @property int $total_steps
 */
class IpiTask extends Model
{
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'int';
    protected $casts = [
        'title_translations' => 'array',
        'description_translations' => 'array',
    ];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(IpiChapter::class, 'chapter_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(IpiTaskAction::class, 'task_id')->orderBy('sort_order');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(IpiTaskReward::class, 'task_id');
    }
}

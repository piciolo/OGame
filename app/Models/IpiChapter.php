<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $sort_order
 * @property string $title
 * @property bool $enabled
 */
class IpiChapter extends Model
{
    protected $guarded = [];
    public $incrementing = false;
    protected $keyType = 'int';
    protected $casts = [
        'enabled' => 'boolean',
        'title_translations' => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(IpiTask::class, 'chapter_id')->orderBy('sort_order');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(IpiChapterReward::class, 'chapter_id');
    }
}

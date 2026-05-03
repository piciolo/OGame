<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $chapter_id
 * @property int $resource_index  0=metal, 1=crystal, 2=deuterium, 3=energy, 4=dark_matter
 * @property int $quantity
 */
class IpiChapterReward extends Model
{
    protected $guarded = [];

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(IpiChapter::class, 'chapter_id');
    }
}

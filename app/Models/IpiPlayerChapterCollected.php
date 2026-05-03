<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $chapter_id
 * @property \Illuminate\Support\Carbon $collected_at
 */
class IpiPlayerChapterCollected extends Model
{
    protected $table = 'ipi_player_chapter_collected';
    protected $guarded = [];
    protected $casts = [
        'collected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(IpiChapter::class, 'chapter_id');
    }
}

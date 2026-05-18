<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $item_id
 * @property int $price
 * @property string $status
 * @property int $change_count
 * @property Carbon|null $revealed_at
 * @property Carbon|null $consumed_at
 * @property Carbon $expires_at
 */
#[Fillable([
    'user_id', 'item_id', 'price', 'status', 'change_count',
    'revealed_at', 'consumed_at', 'expires_at',
])]
#[Table(name: 'import_export_offers')]
class ImportExportOffer extends Model
{
    protected $casts = [
        'revealed_at' => 'datetime',
        'consumed_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ImportExportItem::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

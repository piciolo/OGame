<?php

namespace OGame\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OGame\Enums\InventoryCategory;

/**
 * @property int $id
 * @property int $user_id
 * @property string $item_type
 * @property string|null $tier
 * @property InventoryCategory $category
 * @property string $activation_type
 * @property array<string,mixed>|null $payload
 * @property string $status
 * @property Carbon $acquired_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $consumed_at
 * @property string $source
 * @property int|null $source_ref
 */
#[Fillable([
        'user_id',
        'item_type',
        'tier',
        'category',
        'activation_type',
        'payload',
        'status',
        'acquired_at',
        'activated_at',
        'consumed_at',
        'source',
        'source_ref',
    ])]
#[Table(name: 'user_items')]
class UserItem extends Model
{
    use HasFactory;
    protected $casts = [
        'payload' => 'array',
        'category' => InventoryCategory::class,
        'acquired_at' => 'datetime',
        'activated_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * OGame-compatible ref SHA-1 used as item identity in the JS inventoryObj.
     * A single ref represents a (item_type,tier) stack — the JS groups by ref
     * and shows a quantity.
     */
    public function stackRef(): string
    {
        return self::refFor($this->item_type, $this->tier);
    }

    public static function refFor(string $itemType, string|null $tier): string
    {
        return sha1('user_item:' . $itemType . ':' . ($tier ?? ''));
    }
}

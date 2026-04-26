<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit log for Materia Oscura purchases at the Shop.
 *
 * @property int $id
 * @property int $user_id
 * @property int $shop_item_id
 * @property int|null $user_item_id
 * @property int $dm_spent
 * @property string $item_name
 * @property string|null $ip_address
 * @property \Illuminate\Support\Carbon $created_at
 */
class ShopPurchase extends Model
{
    protected $table = 'shop_purchases';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'shop_item_id',
        'user_item_id',
        'dm_spent',
        'item_name',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'dm_spent' => 'int',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shopItem(): BelongsTo
    {
        return $this->belongsTo(ShopItem::class, 'shop_item_id');
    }

    public function userItem(): BelongsTo
    {
        return $this->belongsTo(UserItem::class, 'user_item_id');
    }
}

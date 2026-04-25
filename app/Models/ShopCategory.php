<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $sort_order
 */
class ShopCategory extends Model
{
    protected $table = 'shop_categories';

    protected $fillable = ['key', 'name', 'sort_order'];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ShopItem::class, 'shop_item_category');
    }
}

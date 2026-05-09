<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $sort_order
 */
#[Fillable(['key', 'name', 'sort_order'])]
#[Table(name: 'shop_categories')]
class ShopCategory extends Model
{
    public function items(): BelongsToMany
    {
        return $this->belongsToMany(ShopItem::class, 'shop_item_category');
    }
}

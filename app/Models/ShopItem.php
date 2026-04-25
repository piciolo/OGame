<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $ref
 * @property string $name
 * @property string|null $description
 * @property string|null $extended_description
 * @property string|null $effect_description
 * @property int $price_dm
 * @property int|null $price_dm_original
 * @property string $price_label
 * @property string|null $price_label_original
 * @property int|null $duration_seconds
 * @property string|null $duration_label
 * @property string $rarity
 * @property string $image
 * @property string|null $image_fallback
 * @property bool $is_lifeform
 * @property string|null $booster_type
 * @property string|null $tier_key
 * @property int $sort_order
 */
class ShopItem extends Model
{
    protected $table = 'shop_items';

    protected $fillable = [
        'ref', 'name', 'description', 'extended_description', 'effect_description', 'rules_description',
        'price_dm', 'price_dm_original', 'price_label', 'price_label_original',
        'duration_seconds', 'duration_label',
        'rarity', 'image', 'image_fallback',
        'is_lifeform', 'booster_type', 'tier_key',
        'sort_order',
    ];

    protected $casts = [
        'price_dm' => 'integer',
        'price_dm_original' => 'integer',
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_lifeform' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ShopCategory::class, 'shop_item_category');
    }
}

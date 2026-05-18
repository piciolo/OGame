<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $ref
 * @property string $category
 * @property string $type
 * @property string $rarity
 * @property string $name
 * @property string $description
 * @property int $effect_value
 * @property int|null $duration_seconds
 * @property string $icon_path
 * @property int $drop_weight
 * @property int $change_dm_cost
 * @property int $price_base
 * @property array<string,array<string,string>>|null $translations
 */
#[Fillable([
    'ref', 'category', 'type', 'rarity', 'name', 'description',
    'effect_value', 'duration_seconds', 'icon_path',
    'drop_weight', 'change_dm_cost', 'price_base', 'translations',
])]
#[Table(name: 'import_export_items')]
class ImportExportItem extends Model
{
    protected $casts = [
        'translations' => 'array',
    ];

    public function localizedName(string $locale): string
    {
        return $this->translations[$locale]['name'] ?? $this->name;
    }

    public function localizedDescription(string $locale): string
    {
        return $this->translations[$locale]['description'] ?? $this->description;
    }
}

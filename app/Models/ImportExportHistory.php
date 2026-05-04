<?php

namespace OGame\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $item_id
 * @property string $acquisition_method
 * @property int $paid_metal
 * @property int $paid_crystal
 * @property int $paid_deuterium
 * @property int $paid_honor
 * @property int $paid_dm
 * @property int|null $source_planet_id
 * @property string|null $source_body_type
 */
class ImportExportHistory extends Model
{
    protected $table = 'import_export_history';

    protected $fillable = [
        'user_id', 'item_id', 'acquisition_method',
        'paid_metal', 'paid_crystal', 'paid_deuterium', 'paid_honor', 'paid_dm',
        'source_planet_id', 'source_body_type',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ImportExportItem::class, 'item_id');
    }
}

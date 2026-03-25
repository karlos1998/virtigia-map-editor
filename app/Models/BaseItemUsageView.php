<?php

namespace App\Models;

class BaseItemUsageView extends DynamicModel
{
    protected $table = 'base_item_usage_views';

    protected $primaryKey = 'base_item_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'base_item_id',
        'is_in_use',
        'source_count',
        'sources',
    ];

    protected $casts = [
        'is_in_use' => 'boolean',
        'source_count' => 'integer',
        'sources' => 'array',
    ];

    public function baseItem()
    {
        return $this->belongsTo(BaseItem::class);
    }
}

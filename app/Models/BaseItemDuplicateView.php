<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaseItemDuplicateView extends DynamicModel
{
    protected $table = 'base_item_duplicate_views';

    protected $fillable = [
        'duplicate_base_item_id',
        'used_base_item_id',
        'duplicate_group_hash',
        'normalized_name',
        'name',
        'category',
        'rarity',
        'need_level',
        'duplicate_src',
        'used_src',
        'duplicate_usage_source_count',
        'used_usage_source_count',
        'refreshed_at',
    ];

    protected function casts(): array
    {
        return [
            'need_level' => 'integer',
            'duplicate_usage_source_count' => 'integer',
            'used_usage_source_count' => 'integer',
            'refreshed_at' => 'datetime',
        ];
    }

    public function duplicateBaseItem(): BelongsTo
    {
        return $this->belongsTo(BaseItem::class, 'duplicate_base_item_id');
    }

    public function usedBaseItem(): BelongsTo
    {
        return $this->belongsTo(BaseItem::class, 'used_base_item_id');
    }
}

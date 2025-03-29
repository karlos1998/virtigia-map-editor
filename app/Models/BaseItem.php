<?php

namespace App\Models;

use App\Enums\BaseItemCategory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Engines\MeilisearchEngine;
use Laravel\Scout\Searchable;

class BaseItem extends DynamicModel
{

    use SoftDeletes;
    use Searchable;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'base_items_' . $this->getConnectionName();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

//    public function searchableUsing()
//    {
//        return app(MeiliSearchEngine::class)->setConnection('retro');
//    }

    protected $fillable = [
        'name',
        'src',
        'stats',
        'cl',
        'pr',
        'edited_manually',
        'attributes'
    ];

    protected $casts = [
        'attributes' => 'json',
        'category' => BaseItemCategory::class,
        'edited_manually' => 'boolean'
    ];

    public function shops()
    {
        return $this->belongsToMany(Shop::class, ShopItem::class, 'item_id', 'shop_id')
            ->withPivot('position');
    }

    public function baseNpcs()
    {
        return $this->belongsToMany(BaseNpc::class, BaseNpcLoot::class, 'base_item_id', 'base_npc_id');
    }

    public function isInUse()
    {
        return $this->shops()->exists() ||
            $this->baseNpcs()->exists();
    }
}

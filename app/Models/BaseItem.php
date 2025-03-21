<?php

namespace App\Models;

use App\Enums\BaseItemCategory;

class BaseItem extends DynamicModel
{
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
        return $this->shops()->exists() &&
            $this->baseNpcs()->exists();
    }
}

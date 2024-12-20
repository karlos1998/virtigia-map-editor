<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    public function items()
    {
        return $this->belongsToMany(BaseItem::class, ShopItem::class, 'shop_id', 'item_id')
            ->withPivot('position');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Hotel extends DynamicModel
{
    protected $fillable = [
        'name',
    ];

    public function rooms(): HasMany
    {
        return $this->hasMany(HotelRoom::class);
    }

    public function baseItems(): HasManyThrough
    {
        return $this->hasManyThrough(BaseItem::class, HotelRoom::class, 'hotel_id', 'id', 'id', 'base_item_id');
    }
}

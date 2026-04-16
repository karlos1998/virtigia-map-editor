<?php

namespace App\Models;

use App\Enums\BaseItemCurrency;
use App\Enums\HotelPeriod;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Hotel extends DynamicModel
{
    protected $fillable = [
        'name',
        'currency',
        'period',
    ];

    protected $casts = [
        'currency' => BaseItemCurrency::class,
        'period' => HotelPeriod::class,
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

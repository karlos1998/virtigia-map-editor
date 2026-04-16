<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelRoom extends DynamicModel
{
    protected $fillable = [
        'hotel_id',
        'base_item_id',
        'door_id',
    ];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function baseItem(): BelongsTo
    {
        return $this->belongsTo(BaseItem::class, 'base_item_id');
    }

    public function door(): BelongsTo
    {
        return $this->belongsTo(Door::class, 'door_id');
    }
}

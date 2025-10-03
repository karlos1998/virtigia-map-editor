<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewableMapItem extends DynamicModel
{
    use HasFactory;

    protected $table = 'renewable_map_items';

    protected $fillable = [
        'map_id',
        'base_item_id',
        'x',
        'y',
        'respawn_time_seconds',
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

    public function baseItem()
    {
        return $this->belongsTo(BaseItem::class);
    }
}

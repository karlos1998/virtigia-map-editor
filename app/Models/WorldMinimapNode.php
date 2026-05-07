<?php

namespace App\Models;

class WorldMinimapNode extends DynamicModel
{
    protected $fillable = [
        'map_id',
        'x',
        'y',
    ];

    public function map()
    {
        return $this->belongsTo(Map::class, 'map_id');
    }
}


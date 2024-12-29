<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpcLocation extends DynamicModel
{
    protected $fillable = ['map_id', 'x', 'y'];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}

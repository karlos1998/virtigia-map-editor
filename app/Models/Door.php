<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Door extends DynamicModel
{

    protected $fillable = [
        'map_id',
        'x',
        'y',

        'go_map_id',
        'go_x',
        'go_y',
    ];

    public function targetMap() {
        return $this->hasOne(Map::class, 'id', 'go_map_id');
    }

    public function doubleSided() {
        return self::where('map_id', $this->go_map_id)
            ->where('go_map_id', $this->map_id)
            ->where('go_x', $this->x)
            ->where('go_y', $this->y)
            ->exists();
    }

    public function map()
    {
        return $this->hasOne(Map::class, 'id', 'map_id');
    }
}

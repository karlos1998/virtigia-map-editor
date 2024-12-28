<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Door extends DynamicModel
{
    public function targetMap() {
        return $this->hasOne(Map::class, 'id', 'go_map_id');
    }
}

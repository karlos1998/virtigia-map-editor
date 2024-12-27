<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends DynamicModel
{
    public function npcs() {
        return $this->hasMany(Npc::class);
    }
}

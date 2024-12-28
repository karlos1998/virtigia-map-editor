<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends DynamicModel
{
    public function npcs() {
//        return $this->hasMany(Npc::class);
        return $this->belongsToMany(Npc::class, NpcLocation::class)->withPivot(['x', 'y']);
    }

    public function doors() {
        return $this->hasMany(Door::class);
    }
}

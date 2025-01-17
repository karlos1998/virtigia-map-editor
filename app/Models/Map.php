<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends DynamicModel
{
    protected $fillable = [
        'name',
        'src',
        'x',
        'y',
        'col',
        'battleground',
        'water',
        'pvp',
    ];

    public function npcs() {
        return $this->belongsToMany(Npc::class, NpcLocation::class)->withPivot(['id', 'x', 'y']);
    }

    public function doors() {
        return $this->hasMany(Door::class);
    }
}

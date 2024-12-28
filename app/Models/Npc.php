<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Npc extends DynamicModel
{
    public function base()
    {
        return $this->belongsTo(BaseNpc::class, 'base_npc_id');
    }

    public function locations()
    {
        return $this->hasMany(NpcLocation::class);
    }
}

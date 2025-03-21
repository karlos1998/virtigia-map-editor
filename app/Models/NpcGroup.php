<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NpcGroup extends DynamicModel
{
    public function npcs()
    {
        return $this->hasMany(Npc::class, 'group_id');
    }
}

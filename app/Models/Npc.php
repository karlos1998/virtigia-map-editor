<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Npc extends DynamicModel
{

    protected $fillable = ['grp'];

    protected $casts = ['grp' => 'boolean'];

    public function base()
    {
        return $this->belongsTo(BaseNpc::class, 'base_npc_id');
    }

    public function locations()
    {
        return $this->hasMany(NpcLocation::class);
    }

    public function dialog()
    {
        return $this->belongsTo(Dialog::class);
    }
}

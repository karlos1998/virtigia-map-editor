<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Npc extends DynamicModel
{

    protected $fillable = ['group_id', 'manually_group_detached'];

    protected $casts = ['manually_group_detached' => 'boolean'];

    protected $attributes = [
        'manually_group_detached' => false
    ];

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

    public function group()
    {
        return $this->belongsTo(NpcGroup::class, 'group_id');
    }
}

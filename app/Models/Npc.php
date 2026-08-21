<?php

namespace App\Models;

class Npc extends DynamicModel
{
    protected $fillable = [
        'group_id',
        'manually_group_detached',
        'enabled',
        'auto_start_dialog',
        'auto_start_dialog_range',
    ];

    protected $casts = [
        'manually_group_detached' => 'boolean',
        'enabled' => 'boolean',
        'auto_start_dialog' => 'boolean',
        'auto_start_dialog_range' => 'integer',
    ];

    protected $attributes = [
        'manually_group_detached' => false,
        'enabled' => true,
        'auto_start_dialog' => false,
        'auto_start_dialog_range' => 1,
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

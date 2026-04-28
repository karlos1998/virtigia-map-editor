<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class MobSpecies extends DynamicModel
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function baseNpcs()
    {
        return $this->belongsToMany(BaseNpc::class, 'base_npc_mob_species');
    }
}


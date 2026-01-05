<?php

namespace App\Models;

use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialAttack extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\SpecialAttackFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'attack_type',
        'charge_turns',
        'target',
        'random_target',
    ];

    protected $casts = [
        'attack_type' => SpecialAttackType::class,
        'target' => SpecialAttackTarget::class,
        'charge_turns' => 'integer',
        'random_target' => 'boolean',
    ];

    public function effects(): HasMany
    {
        return $this->hasMany(SpecialAttackEffect::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(SpecialAttackDamage::class);
    }

    public function baseNpcs()
    {
        return $this->belongsToMany(BaseNpc::class, 'base_npc_special_attacks');
    }
}

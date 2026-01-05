<?php

namespace App\Models;

use App\Enums\SpecialAttackEffectType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialAttackEffect extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\SpecialAttackEffectFactory> */
    use HasFactory;

    protected $fillable = [
        'special_attack_id',
        'type',
        'value',
        'duration',
    ];

    protected $casts = [
        'type' => SpecialAttackEffectType::class,
        'value' => 'float',
        'duration' => 'integer',
    ];

    public function specialAttack(): BelongsTo
    {
        return $this->belongsTo(SpecialAttack::class);
    }
}

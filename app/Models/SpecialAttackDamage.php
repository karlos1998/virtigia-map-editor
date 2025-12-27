<?php

namespace App\Models;

use App\Enums\SpecialAttackElement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialAttackDamage extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\SpecialAttackDamageFactory> */
    use HasFactory;

    protected $fillable = [
        'special_attack_id',
        'element',
        'min_damage',
        'max_damage',
    ];

    protected $casts = [
        'element' => SpecialAttackElement::class,
        'min_damage' => 'integer',
        'max_damage' => 'integer',
    ];

    public function specialAttack(): BelongsTo
    {
        return $this->belongsTo(SpecialAttack::class);
    }
}

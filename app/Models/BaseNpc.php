<?php

namespace App\Models;

use App\Enums\Profession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseNpc extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\BaseNpcFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'src',
        'lvl',
        'type',
        'wt',
        'rank',
        'category',
        'profession'
    ];

    protected $casts = [
        'profession' => Profession::class,
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     * Lokalizacja dla np "Królika"
     * Nie są to respy jednego moba, tylko np kilkadziesiąt miejsc na mapie gdzie taki sam bazowy mob wystepuje
     */
    public function locations()
    {
        return $this->hasMany(Npc::class);
    }

    public function loots()
    {
        return $this->belongsToMany(BaseItem::class, 'base_npc_loots');
    }
}

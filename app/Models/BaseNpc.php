<?php

namespace App\Models;

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
}

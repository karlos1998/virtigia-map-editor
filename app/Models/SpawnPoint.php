<?php

namespace App\Models;

use App\Enums\Profession;
use Illuminate\Database\Eloquent\Model;

class SpawnPoint extends DynamicModel
{

    protected $fillable = ['map_id', 'x', 'y', 'profession'];
    protected $casts = [
        'profession' => Profession::class,
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}

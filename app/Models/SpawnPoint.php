<?php

namespace App\Models;

use App\Enums\Profession;
use Illuminate\Database\Eloquent\Model;

class SpawnPoint extends DynamicModel
{
    protected $casts = [
        'profession' => Profession::class,
    ];

    public function map()
    {
        return $this->belongsTo(Map::class);
    }
}

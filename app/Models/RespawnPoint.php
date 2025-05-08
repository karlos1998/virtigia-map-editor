<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespawnPoint extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\RespawnPointFactory> */
    use HasFactory;

    public function map()
    {
        return $this->belongsTo(Map::class);
    }

}

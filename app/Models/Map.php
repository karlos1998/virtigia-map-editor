<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
//    protected $connection = 'retro';
    protected $table = 'maps';
    protected $guarded = [];

    public function setConnectionName(string $connection): self
    {
        return $this->setConnection($connection);
    }
}

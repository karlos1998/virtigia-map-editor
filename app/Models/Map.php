<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Map extends Model
{
    public function setConnectionName(string $connection): self
    {
        return $this->setConnection($connection);
    }
}

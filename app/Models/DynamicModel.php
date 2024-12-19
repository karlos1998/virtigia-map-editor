<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class DynamicModel extends Model
{
    public function setConnectionName(string $connection): self
    {
        return $this->setConnection($connection);
    }
}

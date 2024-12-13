<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseItem extends Model
{
    public function setConnectionName(string $connection): self
    {
        return $this->setConnection($connection);
    }
}

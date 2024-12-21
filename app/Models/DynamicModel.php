<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class DynamicModel extends Model
{
    protected static ?string $globalConnection = null;

    public function setConnectionName(string $connection): self
    {
        $this->setConnection($connection);
        return $this;
    }

    public static function setGlobalConnection(string $connection): void
    {
        self::$globalConnection = $connection;
    }

    public function getConnectionName(): ?string
    {
        return static::$globalConnection ?? parent::getConnectionName();
    }
}

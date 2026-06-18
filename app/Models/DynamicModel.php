<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

abstract class DynamicModel extends Model
{
    use LogsActivity;

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

    public static function clearGlobalConnection(): void
    {
        self::$globalConnection = null;
    }

    public function getConnectionName(): ?string
    {
        return parent::getConnectionName() ?? static::$globalConnection;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}

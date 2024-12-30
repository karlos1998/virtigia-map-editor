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

    public function getConnectionName(): ?string
    {
        return static::$globalConnection ?? parent::getConnectionName();
    }


    /**
     * @return LogOptions
     * Activity logs options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}

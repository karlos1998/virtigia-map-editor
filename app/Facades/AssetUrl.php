<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AssetUrl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'asset-url';
    }
}

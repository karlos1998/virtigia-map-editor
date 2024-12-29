<?php

namespace App\Models;

class BaseItem extends DynamicModel
{
    protected $fillable = [
        'name',
        'src',
        'stats',
        'cl',
        'pr',
    ];
}

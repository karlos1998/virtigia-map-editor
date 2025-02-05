<?php

namespace App\Models;

use App\Enums\BaseItemCategory;

class BaseItem extends DynamicModel
{
    protected $fillable = [
        'name',
        'src',
        'stats',
        'cl',
        'pr',
    ];

    protected $casts = [
        'attributes' => 'json',
        'category' => BaseItemCategory::class,
    ];
}

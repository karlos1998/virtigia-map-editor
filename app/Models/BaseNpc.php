<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseNpc extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\BaseNpcFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'src',
        'lvl',
        'type',
        'wt',
    ];
}

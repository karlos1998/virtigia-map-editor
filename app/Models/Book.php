<?php

namespace App\Models;

class Book extends DynamicModel
{
    protected $fillable = [
        'title',
        'content',
    ];
}

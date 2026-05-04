<?php

namespace App\Models;

class Audio extends DynamicModel
{
    protected $table = 'audios';

    protected $fillable = [
        'name',
        'path',
    ];
}

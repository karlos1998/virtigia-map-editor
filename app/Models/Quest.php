<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quest extends DynamicModel
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(QuestStep::class);
    }
}

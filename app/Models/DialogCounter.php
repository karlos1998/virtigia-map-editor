<?php

namespace App\Models;

use App\Enums\DialogCounterScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DialogCounter extends DynamicModel
{
    use HasFactory;

    protected $fillable = ['name', 'scope'];

    protected function casts(): array
    {
        return [
            'scope' => DialogCounterScope::class,
        ];
    }
}

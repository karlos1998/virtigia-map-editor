<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DialogNode extends DynamicModel
{
    use HasFactory;

    protected $fillable = ['content', 'type', 'position'];

    protected $casts = [
        'position' => 'json',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(DialogNodeOption::class, 'node_id');
    }

    public function dialog(): HasOne
    {
        return $this->hasOne(Dialog::class, 'id', 'dialog_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dialog extends Model
{
    /** @use HasFactory<\Database\Factories\DialogFactory> */
    use HasFactory;

    protected $table = 'dialog';

    protected $fillable = ['title', 'content', 'group_id'];

    public function options(): HasMany
    {
        return $this->hasMany(DialogOption::class, 'dialog_id');
    }

    public function group(): HasOne
    {
        return $this->hasOne(DialogGroup::class, 'id', 'group_id');
    }
}

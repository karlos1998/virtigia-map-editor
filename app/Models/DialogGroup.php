<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DialogGroup extends Model
{
    /** @use HasFactory<\Database\Factories\DialogGroupFactory> */
    use HasFactory;

    protected $table = 'dialog_group';

    protected $fillable = ['id'];

    public function connections(): HasMany
    {
        return $this->hasMany(DialogConnection::class, 'source_group_id');
    }
}

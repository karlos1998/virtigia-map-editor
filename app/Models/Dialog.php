<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dialog extends DynamicModel
{
    use HasFactory;

    public function edges(): HasMany
    {
        return $this->hasMany(DialogEdge::class, 'source_dialog_id');
    }

    public function nodes()
    {
        return $this->hasMany(DialogNode::class, 'source_dialog_id');
    }
}

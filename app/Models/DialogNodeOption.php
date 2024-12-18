<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogNodeOption extends Model
{
    use HasFactory;
    protected $fillable = ['content', 'rules'];

    protected $casts = [
        'rules' => 'json'
    ];

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class, 'dialog_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(DialogNode::class, 'node_id');
    }
}

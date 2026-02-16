<?php

namespace App\Models;

use App\Enums\DialogNodeOptionAdditionalAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogNodeOption extends DynamicModel
{
    use HasFactory;

    protected $fillable = ['label', 'rules', 'additional_action', 'order', 'cooldown'];

    protected $casts = [
        'rules' => 'json',
        'additional_action' => DialogNodeOptionAdditionalAction::class,
        'cooldown' => 'integer',
    ];

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class, 'dialog_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(DialogNode::class, 'node_id');
    }

    public function edges()
    {
        return $this->hasMany(DialogEdge::class, 'source_option_id');
    }
}

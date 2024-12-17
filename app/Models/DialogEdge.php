<?php

namespace App\Models;

use App\Traits\HasDialogRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogEdge extends Model
{
    use HasFactory;
    use HasDialogRules;

    protected $fillable = ['rules'];

    public function sourceDialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class, 'source_dialog_id');
    }

    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(DialogNodeOption::class, 'source_option_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(DialogNode::class, 'target_node_id');
    }
}

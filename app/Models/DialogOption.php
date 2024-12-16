<?php

namespace App\Models;

use App\Traits\HasDialogRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogOption extends Model
{
    /** @use HasFactory<\Database\Factories\DialogOptionFactory> */
    use HasFactory;
    use HasDialogRules;

    protected $table = 'dialog_option';

    protected $fillable = ['dialog_id', 'content', 'rules'];

    public function dialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class, 'dialog_id');
    }
}

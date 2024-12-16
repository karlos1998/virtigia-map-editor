<?php

namespace App\Models;

use App\Traits\HasDialogRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DialogConnection extends Model
{
    /** @use HasFactory<\Database\Factories\DialogConnectionFactory> */
    use HasFactory;
    use HasDialogRules;

    protected $table = 'dialog_connection';

    protected $fillable = ['source_group_id', 'source_option_id', 'target_dialog_id', 'rules'];

    public function sourceGroup(): BelongsTo
    {
        return $this->belongsTo(DialogGroup::class, 'source_group_id');
    }

    public function sourceOption(): BelongsTo
    {
        return $this->belongsTo(DialogOption::class, 'source_option_id');
    }

    public function targetDialog(): BelongsTo
    {
        return $this->belongsTo(Dialog::class, 'target_dialog_id');
    }
}

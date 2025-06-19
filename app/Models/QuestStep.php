<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class QuestStep extends DynamicModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'quest_id',
        'visible_in_quest_list',
    ];

    protected $casts = [
        'visible_in_quest_list' => 'boolean',
    ];

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    /**
     * Get the auto progress settings for this quest step.
     */
    public function autoProgress(): HasOne
    {
        return $this->hasOne(QuestStepAutoProgress::class);
    }
}

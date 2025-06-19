<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestStepAutoProgress extends DynamicModel
{
    use HasFactory;

    protected $table = 'quest_step_auto_progresses';

    protected $fillable = [
        'quest_step_id',
        'type',
        'time_seconds',
    ];

    /**
     * Get the quest step that owns this auto progress.
     */
    public function questStep(): BelongsTo
    {
        return $this->belongsTo(QuestStep::class);
    }

    /**
     * Get the mobs that need to be killed for this auto progress.
     */
    public function mobs(): HasMany
    {
        return $this->hasMany(QuestStepAutoProgressMob::class);
    }
}

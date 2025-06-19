<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestStepAutoProgressMob extends DynamicModel
{
    use HasFactory;

    protected $fillable = [
        'quest_step_auto_progress_id',
        'base_npc_id',
        'quantity',
    ];

    /**
     * Get the auto progress that owns this mob.
     */
    public function autoProgress(): BelongsTo
    {
        return $this->belongsTo(QuestStepAutoProgress::class, 'quest_step_auto_progress_id');
    }

    /**
     * Get the base NPC that needs to be killed.
     */
    public function baseNpc(): BelongsTo
    {
        return $this->belongsTo(BaseNpc::class);
    }
}

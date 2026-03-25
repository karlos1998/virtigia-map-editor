<?php

namespace App\Models;

class QuestStepGuideView extends DynamicModel
{
    protected $table = 'quest_step_guide_views';

    protected $primaryKey = 'quest_step_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'quest_step_id',
        'guide_count',
        'guides',
    ];

    protected $casts = [
        'guide_count' => 'integer',
        'guides' => 'array',
    ];

    public function questStep()
    {
        return $this->belongsTo(QuestStep::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestWalkthrough extends DynamicModel
{
    protected $table = 'quest_walkthroughs';

    protected $primaryKey = 'quest_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'quest_id',
        'generator_version',
        'status',
        'content_hash',
        'walkthrough',
        'warnings_count',
        'generated_at',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'generator_version' => 'integer',
            'walkthrough' => 'array',
            'warnings_count' => 'integer',
            'generated_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }
}

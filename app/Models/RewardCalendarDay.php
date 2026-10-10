<?php

namespace App\Models;

use App\Enums\RewardCalendarDayType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardCalendarDay extends DynamicModel
{
    protected $table = 'reward_calendar_days';

    protected $fillable = [
        'reward_calendar_id',
        'month',
        'day',
        'type',
        'rewards',
        'tiers',
    ];

    protected $casts = [
        'month' => 'integer',
        'day' => 'integer',
        'type' => RewardCalendarDayType::class,
        'rewards' => 'array',
        'tiers' => 'array',
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(RewardCalendar::class, 'reward_calendar_id');
    }
}

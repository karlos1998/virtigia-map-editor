<?php

namespace App\Models;

use App\Enums\RewardCalendarStyle;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RewardCalendar extends DynamicModel
{
    protected $table = 'reward_calendars';

    protected $fillable = [
        'name',
        'year',
        'style',
        'enabled',
    ];

    protected $casts = [
        'year' => 'integer',
        'style' => RewardCalendarStyle::class,
        'enabled' => 'boolean',
    ];

    public function days(): HasMany
    {
        return $this->hasMany(RewardCalendarDay::class, 'reward_calendar_id')
            ->orderBy('month')
            ->orderBy('day');
    }

    public function isRecurring(): bool
    {
        return $this->year === null;
    }
}

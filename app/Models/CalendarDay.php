<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarDay extends DynamicModel
{
    use HasFactory;

    protected $table = 'calendar_days';

    protected $fillable = [
        'day',
        'month',
        'year',
        'name',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(RewardCalendarItem::class, 'calendar_day_id');
    }
}

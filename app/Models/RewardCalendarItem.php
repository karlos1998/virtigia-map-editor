<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardCalendarItem extends DynamicModel
{
    protected $table = 'reward_calendar_items';

    protected $fillable = [
        'calendar_day_id',
        'base_item_id',
        'quantity',
    ];

    public function calendar(): BelongsTo
    {
        return $this->belongsTo(CalendarDay::class, 'calendar_day_id');
    }

    public function baseItem(): BelongsTo
    {
        return $this->belongsTo(BaseItem::class, 'base_item_id');
    }
}

<?php

namespace App\Http\Requests;

use App\Models\RewardCalendar;
use App\Models\RewardCalendarDay;

class UpdateRewardCalendarDayRequest extends StoreRewardCalendarDayRequest
{
    protected function dayIsTaken(RewardCalendar $calendar, int $month, int $day): bool
    {
        /** @var RewardCalendarDay|null $current */
        $current = $this->route('rewardCalendarDay');

        return $calendar->days()
            ->where('month', $month)
            ->where('day', $day)
            ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
            ->exists();
    }
}

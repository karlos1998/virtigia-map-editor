<?php

namespace App\Http\Requests;

use App\Enums\RewardCalendarDayType;
use App\Models\RewardCalendar;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRewardCalendarDayRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'day' => ['required', 'integer', 'between:1,31'],
            'type' => ['required', Rule::enum(RewardCalendarDayType::class)],

            'rewards' => ['nullable', 'array', 'required_if:type,normal'],
            ...self::rewardConfigRules('rewards'),

            'tiers' => ['nullable', 'array', 'required_if:type,special', 'min:1'],
            'tiers.*.minOpenedDays' => ['required', 'integer', 'min:0', 'max:366', 'distinct'],
            'tiers.*.rewards' => ['required', 'array'],
            ...self::rewardConfigRules('tiers.*.rewards'),
        ];
    }

    /**
     * Reguły dla konfiguracji nagród w formacie kuferka (LootChestEditor).
     */
    public static function rewardConfigRules(string $prefix): array
    {
        return [
            "$prefix.mode" => ['required_with:'.$prefix, Rule::in(['random', 'guaranteed'])],
            "$prefix.rewardBinding" => ['nullable', Rule::in(['none', 'boundToOwner', 'permanentlyBounded', 'bindsAfterEquip'])],
            "$prefix.minRewards" => ['nullable', 'integer', 'min:0', 'max:999'],
            "$prefix.maxRewards" => ['nullable', 'integer', 'min:0', 'max:999'],
            "$prefix.items" => ['present_with:'.$prefix, 'array'],
            "$prefix.items.*.baseItemId" => ['required', 'integer', 'min:1'],
            "$prefix.items.*.chancePercent" => ['required', 'numeric', 'between:0,100'],
            "$prefix.items.*.minQuantity" => ['required', 'integer', 'between:1,999'],
            "$prefix.items.*.maxQuantity" => ['required', 'integer', 'between:1,999', "gte:$prefix.items.*.minQuantity"],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $month = (int) $this->input('month');
                $day = (int) $this->input('day');
                /** @var RewardCalendar|null $calendar */
                $calendar = $this->route('rewardCalendar');

                if ($month < 1 || $month > 12 || $day < 1) {
                    return;
                }

                // Rok przestępny dla kalendarzy cyklicznych, żeby dało się ustawić 29 lutego.
                $year = $calendar?->year ?? 2024;
                if (! checkdate($month, $day, $year)) {
                    $validator->errors()->add('day', 'Taki dzień nie istnieje w tym miesiącu.');

                    return;
                }

                if ($calendar && $this->dayIsTaken($calendar, $month, $day)) {
                    $validator->errors()->add('day', 'Ten dzień jest już przypisany do kalendarza.');
                }
            },
        ];
    }

    protected function dayIsTaken(RewardCalendar $calendar, int $month, int $day): bool
    {
        return $calendar->days()
            ->where('month', $month)
            ->where('day', $day)
            ->exists();
    }
}

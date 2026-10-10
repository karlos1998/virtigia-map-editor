<?php

namespace App\Services;

use App\Enums\RewardCalendarDayType;
use App\Facades\AssetUrl;
use App\Enums\RewardCalendarStyle;
use App\Models\BaseItem;
use App\Models\RewardCalendar;
use App\Models\RewardCalendarDay;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class RewardCalendarService
{
    /** Strefa czasowa, w której silnik liczy "dzisiejszy" dzień kalendarza. */
    public const TIMEZONE = 'Europe/Warsaw';

    public function getAllForList(): Collection
    {
        return RewardCalendar::query()
            ->with('days')
            ->orderByDesc('enabled')
            ->orderBy('name')
            ->get()
            ->map(fn (RewardCalendar $calendar) => $this->mapCalendar($calendar))
            ->values();
    }

    public function getShowData(RewardCalendar $calendar): array
    {
        $calendar->load('days');

        return [
            'calendar' => $this->mapCalendar($calendar),
            'days' => $calendar->days->map(fn (RewardCalendarDay $day) => $this->mapDay($day))->values(),
            'baseItems' => $this->resolveBaseItems($calendar->days),
            'styles' => $this->styleOptions(),
        ];
    }

    public function styleOptions(): array
    {
        return array_map(
            fn (RewardCalendarStyle $style) => ['value' => $style->value, 'label' => $style->label()],
            RewardCalendarStyle::cases(),
        );
    }

    public function createCalendar(array $validated): RewardCalendar
    {
        return RewardCalendar::query()->create($validated);
    }

    public function updateCalendar(RewardCalendar $calendar, array $validated): void
    {
        $calendar->update($validated);
    }

    public function deleteCalendar(RewardCalendar $calendar): void
    {
        $calendar->delete();
    }

    public function createDay(RewardCalendar $calendar, array $validated): RewardCalendarDay
    {
        return $calendar->days()->create($this->normalizeDayPayload($validated));
    }

    public function updateDay(RewardCalendarDay $day, array $validated): void
    {
        $day->update($this->normalizeDayPayload($validated));
    }

    public function deleteDay(RewardCalendarDay $day): void
    {
        $day->delete();
    }

    /**
     * Zakres trwania kalendarza w danym roku: od pierwszego do ostatniego przypisanego dnia.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public function rangeForYear(RewardCalendar $calendar, int $year): ?array
    {
        $days = $calendar->relationLoaded('days') ? $calendar->days : $calendar->days()->get();

        $dates = $days
            ->filter(fn (RewardCalendarDay $day) => checkdate($day->month, $day->day, $year))
            ->map(fn (RewardCalendarDay $day) => Carbon::create($year, $day->month, $day->day, 0, 0, 0, self::TIMEZONE))
            ->sort()
            ->values();

        if ($dates->isEmpty()) {
            return null;
        }

        return [$dates->first(), $dates->last()->copy()->endOfDay()];
    }

    public function isActiveNow(RewardCalendar $calendar, ?Carbon $now = null): bool
    {
        if (! $calendar->enabled) {
            return false;
        }

        $now = ($now ?? Carbon::now())->copy()->setTimezone(self::TIMEZONE);
        if ($calendar->year !== null && $calendar->year !== $now->year) {
            return false;
        }

        $range = $this->rangeForYear($calendar, $now->year);

        return $range !== null && $now->betweenIncluded($range[0], $range[1]);
    }

    private function normalizeDayPayload(array $validated): array
    {
        $type = RewardCalendarDayType::from($validated['type']);

        if ($type === RewardCalendarDayType::NORMAL) {
            return [
                'month' => $validated['month'],
                'day' => $validated['day'],
                'type' => $type,
                'rewards' => $this->normalizeRewardConfig($validated['rewards'] ?? []),
                'tiers' => null,
            ];
        }

        $tiers = collect($validated['tiers'] ?? [])
            ->map(fn (array $tier) => [
                'minOpenedDays' => (int) $tier['minOpenedDays'],
                'rewards' => $this->normalizeRewardConfig($tier['rewards'] ?? []),
            ])
            ->sortBy('minOpenedDays')
            ->values()
            ->all();

        return [
            'month' => $validated['month'],
            'day' => $validated['day'],
            'type' => $type,
            'rewards' => null,
            'tiers' => $tiers,
        ];
    }

    /**
     * Ujednolica konfigurację nagród do formatu, który silnik czyta tak samo jak zawartość kuferka.
     */
    public function normalizeRewardConfig(array $config): array
    {
        $mode = ($config['mode'] ?? 'random') === 'guaranteed' ? 'guaranteed' : 'random';
        $items = collect($config['items'] ?? [])
            ->map(function (array $item) use ($mode) {
                $minQuantity = max(1, (int) ($item['minQuantity'] ?? 1));

                return [
                    'baseItemId' => (int) $item['baseItemId'],
                    'chancePercent' => $mode === 'guaranteed' ? 100 : min(100, max(0, (float) ($item['chancePercent'] ?? 100))),
                    'minQuantity' => $minQuantity,
                    'maxQuantity' => max($minQuantity, (int) ($item['maxQuantity'] ?? $minQuantity)),
                ];
            })
            ->values();

        $itemsCount = $items->count();
        $minRewards = $mode === 'guaranteed' ? $itemsCount : min($itemsCount, max(0, (int) ($config['minRewards'] ?? 1)));
        $maxRewards = $mode === 'guaranteed' ? $itemsCount : min($itemsCount, max($minRewards, (int) ($config['maxRewards'] ?? $minRewards)));

        return [
            'mode' => $mode,
            'rewardBinding' => $config['rewardBinding'] ?? 'none',
            'minRewards' => $minRewards,
            'maxRewards' => $maxRewards,
            'items' => $items->all(),
        ];
    }

    private function mapCalendar(RewardCalendar $calendar): array
    {
        $now = Carbon::now(self::TIMEZONE);
        $range = $this->rangeForYear($calendar, $calendar->year ?? $now->year);

        return [
            'id' => $calendar->id,
            'name' => $calendar->name,
            'year' => $calendar->year,
            'is_recurring' => $calendar->isRecurring(),
            'style' => $calendar->style?->value,
            'style_label' => $calendar->style?->label(),
            'enabled' => $calendar->enabled,
            'days_count' => $calendar->days->count(),
            'special_days_count' => $calendar->days->where('type', RewardCalendarDayType::SPECIAL)->count(),
            'starts_on' => $range ? $range[0]->toDateString() : null,
            'ends_on' => $range ? $range[1]->toDateString() : null,
            'is_active_now' => $this->isActiveNow($calendar, $now),
        ];
    }

    private function mapDay(RewardCalendarDay $day): array
    {
        return [
            'id' => $day->id,
            'month' => $day->month,
            'day' => $day->day,
            'type' => $day->type?->value,
            'rewards' => $day->rewards,
            'tiers' => $day->tiers,
        ];
    }

    /**
     * Podgląd przedmiotów użytych w nagrodach (nazwa + grafika) do listy dni.
     */
    private function resolveBaseItems(Collection $days): array
    {
        $ids = $days
            ->flatMap(function (RewardCalendarDay $day) {
                $configs = $day->type === RewardCalendarDayType::SPECIAL
                    ? collect($day->tiers ?? [])->pluck('rewards')
                    : collect([$day->rewards]);

                return $configs->flatMap(fn ($config) => collect($config['items'] ?? [])->pluck('baseItemId'));
            })
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return BaseItem::query()
            ->whereIn('id', $ids)
            ->get()
            ->mapWithKeys(fn (BaseItem $item) => [$item->id => [
                'id' => $item->id,
                'name' => $item->name,
                'src' => AssetUrl::item($item->src),
            ]])
            ->all();
    }
}

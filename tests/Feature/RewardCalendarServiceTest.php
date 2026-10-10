<?php

namespace Tests\Feature;

use App\Enums\RewardCalendarDayType;
use App\Http\Requests\StoreRewardCalendarDayRequest;
use App\Http\Requests\StoreRewardCalendarRequest;
use App\Models\RewardCalendar;
use App\Models\RewardCalendarDay;
use App\Services\RewardCalendarService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RewardCalendarServiceTest extends TestCase
{
    public function test_normalize_reward_config_forces_full_chance_in_guaranteed_mode(): void
    {
        $config = app(RewardCalendarService::class)->normalizeRewardConfig([
            'mode' => 'guaranteed',
            'items' => [
                ['baseItemId' => '5', 'chancePercent' => 10, 'minQuantity' => 2, 'maxQuantity' => 1],
                ['baseItemId' => 6, 'chancePercent' => 50, 'minQuantity' => 1, 'maxQuantity' => 3],
            ],
        ]);

        $this->assertSame('guaranteed', $config['mode']);
        $this->assertSame(2, $config['minRewards']);
        $this->assertSame(2, $config['maxRewards']);
        $this->assertSame(100, $config['items'][0]['chancePercent']);
        $this->assertSame(5, $config['items'][0]['baseItemId']);
        $this->assertSame(2, $config['items'][0]['maxQuantity']);
    }

    public function test_normalize_reward_config_clamps_random_reward_counts(): void
    {
        $config = app(RewardCalendarService::class)->normalizeRewardConfig([
            'mode' => 'random',
            'minRewards' => 5,
            'maxRewards' => 9,
            'items' => [
                ['baseItemId' => 5, 'chancePercent' => 30, 'minQuantity' => 1, 'maxQuantity' => 1],
            ],
        ]);

        $this->assertSame(1, $config['minRewards']);
        $this->assertSame(1, $config['maxRewards']);
        $this->assertEquals(30, $config['items'][0]['chancePercent']);
        $this->assertSame('none', $config['rewardBinding']);
    }

    public function test_calendar_is_active_between_first_and_last_day(): void
    {
        $service = app(RewardCalendarService::class);
        $calendar = $this->adventCalendar(year: null);

        $this->assertTrue($service->isActiveNow($calendar, Carbon::create(2026, 12, 1, 0, 30, 0, RewardCalendarService::TIMEZONE)));
        $this->assertTrue($service->isActiveNow($calendar, Carbon::create(2026, 12, 24, 23, 59, 0, RewardCalendarService::TIMEZONE)));
        $this->assertFalse($service->isActiveNow($calendar, Carbon::create(2026, 12, 25, 0, 1, 0, RewardCalendarService::TIMEZONE)));
        $this->assertFalse($service->isActiveNow($calendar, Carbon::create(2026, 11, 30, 23, 0, 0, RewardCalendarService::TIMEZONE)));
    }

    public function test_one_time_calendar_is_only_active_in_its_year(): void
    {
        $service = app(RewardCalendarService::class);
        $calendar = $this->adventCalendar(year: 2025);

        $this->assertTrue($service->isActiveNow($calendar, Carbon::create(2025, 12, 3, 12, 0, 0, RewardCalendarService::TIMEZONE)));
        $this->assertFalse($service->isActiveNow($calendar, Carbon::create(2026, 12, 3, 12, 0, 0, RewardCalendarService::TIMEZONE)));
    }

    public function test_disabled_calendar_is_never_active(): void
    {
        $calendar = $this->adventCalendar(year: null);
        $calendar->enabled = false;

        $this->assertFalse(app(RewardCalendarService::class)->isActiveNow(
            $calendar,
            Carbon::create(2026, 12, 3, 12, 0, 0, RewardCalendarService::TIMEZONE),
        ));
    }

    public function test_store_calendar_request_requires_known_style(): void
    {
        $validator = Validator::make(
            ['name' => 'Wigilia', 'year' => null, 'style' => 'green', 'enabled' => true],
            (new StoreRewardCalendarRequest)->rules(),
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('style', $validator->errors()->toArray());
    }

    public function test_day_request_validates_special_day_tiers(): void
    {
        $rules = (new StoreRewardCalendarDayRequest)->rules();

        $valid = Validator::make([
            'month' => 12,
            'day' => 24,
            'type' => 'special',
            'tiers' => [
                ['minOpenedDays' => 0, 'rewards' => ['mode' => 'guaranteed', 'items' => []]],
                ['minOpenedDays' => 23, 'rewards' => ['mode' => 'random', 'items' => [
                    ['baseItemId' => 1, 'chancePercent' => 50, 'minQuantity' => 1, 'maxQuantity' => 2],
                ]]],
            ],
        ], $rules);
        $this->assertFalse($valid->fails(), json_encode($valid->errors()->toArray()));

        $duplicateThreshold = Validator::make([
            'month' => 12,
            'day' => 24,
            'type' => 'special',
            'tiers' => [
                ['minOpenedDays' => 5, 'rewards' => ['mode' => 'guaranteed', 'items' => []]],
                ['minOpenedDays' => 5, 'rewards' => ['mode' => 'guaranteed', 'items' => []]],
            ],
        ], $rules);
        $this->assertTrue($duplicateThreshold->fails());
    }

    private function adventCalendar(?int $year): RewardCalendar
    {
        $calendar = new RewardCalendar(['name' => 'Adwent', 'year' => $year, 'style' => 'blue', 'enabled' => true]);
        $calendar->setRelation('days', collect([
            new RewardCalendarDay(['month' => 12, 'day' => 1, 'type' => RewardCalendarDayType::NORMAL]),
            new RewardCalendarDay(['month' => 12, 'day' => 2, 'type' => RewardCalendarDayType::NORMAL]),
            new RewardCalendarDay(['month' => 12, 'day' => 24, 'type' => RewardCalendarDayType::SPECIAL]),
        ]));

        return $calendar;
    }
}

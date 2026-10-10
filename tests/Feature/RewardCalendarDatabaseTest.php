<?php

namespace Tests\Feature;

use App\Enums\RewardCalendarDayType;
use App\Enums\RewardCalendarStyle;
use App\Models\DynamicModel;
use App\Models\RewardCalendar;
use App\Services\RewardCalendarService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RewardCalendarDatabaseTest extends TestCase
{
    private string $databasePath;

    private string $defaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = database_path('testing-reward-calendars.sqlite');
        File::delete($this->databasePath);
        File::put($this->databasePath, '');

        config()->set('database.connections.reward_calendar_testing', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('reward_calendar_testing');
        $this->defaultConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('reward_calendar_testing');
        DynamicModel::setGlobalConnection('reward_calendar_testing');
        activity()->disableLogging();

        Schema::create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        (require database_path('migrations/remote/2026_10_10_180000_create_reward_calendars_tables.php'))->up();
    }

    protected function tearDown(): void
    {
        activity()->enableLogging();
        DynamicModel::clearGlobalConnection();
        DB::disconnect('reward_calendar_testing');
        DB::setDefaultConnection($this->defaultConnection);
        File::delete($this->databasePath);

        parent::tearDown();
    }

    public function test_it_stores_normal_and_special_days_in_engine_format(): void
    {
        $service = app(RewardCalendarService::class);
        $calendar = $service->createCalendar([
            'name' => 'Wigilia',
            'year' => null,
            'style' => RewardCalendarStyle::RED->value,
            'enabled' => true,
        ]);

        $service->createDay($calendar, [
            'month' => 12,
            'day' => 1,
            'type' => 'normal',
            'rewards' => [
                'mode' => 'random',
                'minRewards' => 1,
                'maxRewards' => 1,
                'items' => [['baseItemId' => 7, 'chancePercent' => 25, 'minQuantity' => 1, 'maxQuantity' => 2]],
            ],
            'tiers' => [['minOpenedDays' => 1, 'rewards' => []]],
        ]);

        $service->createDay($calendar, [
            'month' => 12,
            'day' => 24,
            'type' => 'special',
            'rewards' => ['mode' => 'random', 'items' => []],
            'tiers' => [
                ['minOpenedDays' => 23, 'rewards' => ['mode' => 'guaranteed', 'items' => [['baseItemId' => 9, 'chancePercent' => 1, 'minQuantity' => 1, 'maxQuantity' => 1]]]],
                ['minOpenedDays' => 0, 'rewards' => ['mode' => 'guaranteed', 'items' => []]],
            ],
        ]);

        $calendar = RewardCalendar::query()->with('days')->findOrFail($calendar->id);
        $this->assertSame(RewardCalendarStyle::RED, $calendar->style);
        $this->assertTrue($calendar->isRecurring());
        $this->assertCount(2, $calendar->days);

        [$normalDay, $specialDay] = $calendar->days->all();
        $this->assertSame(RewardCalendarDayType::NORMAL, $normalDay->type);
        $this->assertNull($normalDay->tiers);
        $this->assertSame(7, $normalDay->rewards['items'][0]['baseItemId']);
        $this->assertSame('none', $normalDay->rewards['rewardBinding']);

        $this->assertSame(RewardCalendarDayType::SPECIAL, $specialDay->type);
        $this->assertNull($specialDay->rewards);
        $this->assertSame([0, 23], array_column($specialDay->tiers, 'minOpenedDays'));
        $this->assertSame(100, $specialDay->tiers[1]['rewards']['items'][0]['chancePercent']);

        $showData = $service->getShowData($calendar);
        $this->assertStringEndsWith('-12-01', $showData['calendar']['starts_on']);
        $this->assertStringEndsWith('-12-24', $showData['calendar']['ends_on']);
        $this->assertSame(2, $showData['calendar']['days_count']);
        $this->assertSame(1, $showData['calendar']['special_days_count']);
    }

    public function test_deleting_calendar_removes_its_days(): void
    {
        $service = app(RewardCalendarService::class);
        $calendar = $service->createCalendar(['name' => 'Halloween', 'year' => 2026, 'style' => 'orange', 'enabled' => true]);
        $service->createDay($calendar, ['month' => 10, 'day' => 31, 'type' => 'normal', 'rewards' => ['mode' => 'guaranteed', 'items' => []]]);

        $service->deleteCalendar($calendar);

        $this->assertSame(0, DB::table('reward_calendar_days')->count());
    }
}

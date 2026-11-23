<?php

namespace Tests\Feature;

use App\Http\Resources\MapDetailResource;
use App\Http\Resources\MapResource;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\MapService;
use App\Services\ThumbnailService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MapWeatherSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/remote/2026_11_23_193625_add_weather_settings_to_maps_table.php';

    protected function setUp(): void
    {
        parent::setUp();
        $connection = config('database.default');
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => $connection]);
        DynamicModel::setGlobalConnection($connection);
        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_city')->default(false);
            $table->string('src')->nullable();
            $table->string('thumbnail_src')->nullable();
            $table->integer('x')->default(10);
            $table->integer('y')->default(10);
            $table->text('col')->nullable();
            $table->text('water')->nullable();
            $table->integer('pvp')->default(0);
            $table->string('battleground')->nullable();
            $table->string('battleground2')->nullable();
            $table->string('battleground3')->nullable();
            $table->string('map_group')->nullable();
            $table->unsignedBigInteger('respawn_point_id')->nullable();
            $table->timestamps();
        });
        Map::withoutEvents(fn () => Map::query()->create(['name' => 'Las', 'src' => 'forest.png']));
        (require database_path(self::MIGRATION))->up();
        $this->actingAs(User::factory()->create(['roles' => ['admin'], 'permissions' => ['world.write']]))
            ->withSession(['world' => 'retro']);
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();
        parent::tearDown();
    }

    public function test_existing_maps_keep_weather_disabled_and_migration_is_reversible(): void
    {
        self::assertNull(Map::query()->firstOrFail()->weather_settings);
        (require database_path(self::MIGRATION))->down();
        self::assertFalse(Schema::hasColumn('maps', 'weather_settings'));
    }

    public function test_editor_persists_weather_and_can_disable_both_effects(): void
    {
        $map = Map::query()->firstOrFail();
        $url = route('maps.update.weather-settings', $map);
        $weather = $this->weather();
        $this->from(route('maps.show', $map))->patch($url, ['weather_settings' => $weather])
            ->assertRedirect(route('maps.show', $map));
        self::assertEquals($weather, $map->refresh()->weather_settings);
        self::assertSame('forest.png', $map->src);
        $this->patch($url, ['weather_settings' => null])->assertRedirect();
        self::assertNull($map->refresh()->weather_settings);
    }

    #[DataProvider('invalidSettings')]
    public function test_editor_rejects_invalid_weather_without_overwriting_saved_settings(string $field, mixed $value): void
    {
        $map = Map::query()->firstOrFail();
        $map->weather_settings = $this->weather();
        $map->save();
        $weather = $this->weather();
        data_set($weather, $field, $value);
        $this->patch(route('maps.update.weather-settings', $map), ['weather_settings' => $weather])
            ->assertSessionHasErrors('weather_settings.'.$field);
        self::assertEquals($this->weather(), $map->refresh()->weather_settings);
    }

    public static function invalidSettings(): array
    {
        return [
            ['rain.density', -0.01], ['rain.density', 1.1], ['rain.speed', 0], ['rain.speed', 3.1],
            ['rain.wind', -2.1], ['rain.wind', 2.1], ['rain.enabled', 'yes'],
            ['fog.opacity', -0.1], ['fog.opacity', 1.1], ['fog.speedX', 1.1], ['fog.speedY', -1.1],
            ['fog.color', 'red'], ['fog.color', '#fff'], ['rain', ['density' => .5]], ['fog', []],
        ];
    }

    public function test_groups_require_enabled_and_unknown_settings_are_rejected(): void
    {
        $map = Map::query()->firstOrFail();
        $this->patch(route('maps.update.weather-settings', $map), ['weather_settings' => ['rain' => ['density' => .5]]])
            ->assertSessionHasErrors(['weather_settings.rain.enabled', 'weather_settings.rain.speed', 'weather_settings.rain.wind']);
        $this->patch(route('maps.update.weather-settings', $map), ['weather_settings' => ['snow' => ['enabled' => true]]])
            ->assertSessionHasErrors('weather_settings');
    }

    public function test_both_map_resources_expose_weather_settings(): void
    {
        $map = Map::query()->firstOrFail();
        $map->weather_settings = $this->weather();
        $map->setRelation('respawnPoint', null);
        foreach ([new MapResource($map), new MapDetailResource($map)] as $resource) {
            self::assertEquals($this->weather(), $resource->resolve()['weather_settings']);
        }
    }

    public function test_copying_a_map_preserves_its_weather(): void
    {
        $map = Map::query()->firstOrFail();
        $map->weather_settings = $this->weather();
        $this->mock(ThumbnailService::class)->shouldReceive('generateThumbnail')->once();
        $copy = Map::withoutEvents(fn () => $this->app->make(MapService::class)->copy($map));
        self::assertNotSame($map->id, $copy->id);
        self::assertEquals($this->weather(), $copy->weather_settings);
    }

    /** @return array<string, array<string, bool|float|string>> */
    private function weather(): array
    {
        return [
            'rain' => ['enabled' => true, 'density' => .5, 'speed' => 1.0, 'wind' => .2],
            'fog' => ['enabled' => true, 'opacity' => .45, 'speedX' => .14, 'speedY' => -.05, 'color' => '#DCE1E6'],
        ];
    }
}

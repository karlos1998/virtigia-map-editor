<?php

namespace Tests\Feature;

use App\Models\Door;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Services\MapService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MapCityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => config('database.default')]);
        DynamicModel::setGlobalConnection(config('database.default'));
        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->integer('x')->default(10);
            $table->integer('y')->default(10);
            $table->text('battleground')->nullable();
            $table->text('battleground2')->nullable();
            $table->foreignId('respawn_point_id')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/remote/2026_10_24_133923_add_is_city_to_maps_table.php'))->up();
        Schema::create('doors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('map_id');
            $table->foreignId('go_map_id');
            $table->integer('x');
            $table->integer('y');
            $table->timestamps();
        });
        Schema::create('respawn_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('map_id')->nullable();
            $table->integer('x');
            $table->integer('y');
            $table->integer('max_steps');
            $table->timestamps();
        });
        $this->actingAs(User::query()->create([
            'login' => 'city-editor', 'name' => 'City Editor', 'email' => 'city@example.com',
            'roles' => ['admin'], 'permissions' => ['world.write'], 'src' => 'retro/avatar.png',
        ]))->withSession(['world' => 'retro']);
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();
        parent::tearDown();
    }

    public function test_related_maps_include_deep_floors_once_and_ignore_all_edges(): void
    {
        $city = $this->map('Miasto');
        $house = $this->map('Dom');
        $floor = $this->map('Piętro');
        $this->door($city, $house);
        $this->door($city, $house);
        $this->door($house, $floor);
        $this->door($floor, $house);
        $this->door($floor, $city);
        foreach ([[0, 4], [4, 0], [9, 4], [4, 9], [10, 4], [4, 10]] as [$x, $y]) {
            $this->door($city, $this->map("Sąsiad $x $y"), $x, $y);
            $this->door($floor, $this->map("Wyjście $x $y"), $x, $y);
        }
        $this->getJson(route('maps.city.related', $city->id))->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.id', $house->id)->assertJsonPath('1.id', $floor->id);
    }

    public function test_confirming_city_updates_only_checked_locations_and_records_history(): void
    {
        $city = $this->map('Miasto');
        $house = $this->map('Dom');
        $floor = $this->map('Piętro');
        $this->door($city, $house);
        $this->door($house, $floor);
        $this->patch(route('maps.update.city', $city->id), [
            'is_city' => true, 'related_map_ids' => [$floor->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($city->fresh()->is_city);
        $this->assertFalse($house->fresh()->is_city);
        $this->assertTrue($floor->fresh()->is_city);
        $this->assertDatabaseHas('activity_log', ['subject_type' => Map::class, 'subject_id' => $city->id, 'event' => 'updated']);
    }

    public function test_unrelated_location_is_rejected_without_partial_update(): void
    {
        $city = $this->map('Miasto');
        $outside = $this->map('Las');
        $this->patch(route('maps.update.city', $city->id), [
            'is_city' => true, 'related_map_ids' => [$outside->id],
        ])->assertSessionHasErrors('related_map_ids');
        $this->assertFalse($city->fresh()->is_city);
        $this->assertFalse($outside->fresh()->is_city);
    }

    public function test_disabling_city_changes_only_root_and_preserves_interior_setting(): void
    {
        $city = $this->map('Miasto', true);
        $house = $this->map('Dom', true);
        $this->door($city, $house);
        $this->patch(route('maps.update.city', $city->id), ['is_city' => false])->assertSessionHasNoErrors();
        $this->assertFalse($city->fresh()->is_city);
        $this->assertTrue($house->fresh()->is_city);
    }

    public function test_bulk_can_enable_and_disable_exhaustion_for_selected_maps_only(): void
    {
        $selected = $this->map('Wybrana');
        $other = $this->map('Pozostała');
        foreach ([true, false] as $isCity) {
            $this->patch(route('maps.bulk.city'), ['map_ids' => [$selected->id], 'is_city' => $isCity])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($isCity, $selected->fresh()->is_city);
            $this->assertFalse($other->fresh()->is_city);
        }
    }

    public function test_invalid_bulk_payload_does_not_change_maps(): void
    {
        $map = $this->map('Mapa');
        $this->patch(route('maps.bulk.city'), ['map_ids' => [$map->id, 99999], 'is_city' => true])
            ->assertSessionHasErrors('map_ids.1');
        $this->patch(route('maps.bulk.city'), ['map_ids' => [], 'is_city' => 'invalid'])
            ->assertSessionHasErrors(['map_ids', 'is_city']);
        $this->assertFalse($map->fresh()->is_city);
    }

    public function test_city_filter_handles_both_values_and_combines_with_battleground_filter(): void
    {
        $city = $this->map('Miasto', true);
        $outside = $this->map('Las');
        $outside->update(['battleground' => 'forest.jpg', 'battleground2' => 'forest2.jpg']);
        $service = app(MapService::class);
        $this->assertSame([$city->id], $service->getAll(['is_city' => true])->collection->pluck('id')->all());
        $this->assertSame([$outside->id], $service->getAll(['is_city' => false])->collection->pluck('id')->all());
        $this->assertCount(0, $service->getAll(['is_city' => false, 'missing_battleground' => true])->collection);
    }

    public function test_index_accepts_city_filter_and_exposes_boolean_flag(): void
    {
        $city = $this->map('Miasto', true);
        $outside = $this->map('Las');
        foreach (['1' => $city->id, '0' => $outside->id] as $filter => $id) {
            $this->get(route('maps.index', ['is_city' => $filter]))->assertOk()
                ->assertInertia(fn (Assert $page): Assert => $page->component('Map/Index')
                    ->has('maps.data', 1)->where('maps.data.0.id', $id)
                    ->where('maps.data.0.is_city', (bool) $filter)->where('filters.is_city', (bool) $filter));
        }
    }

    public function test_city_migration_can_be_rolled_back(): void
    {
        (require database_path('migrations/remote/2026_10_24_133923_add_is_city_to_maps_table.php'))->down();
        $this->assertFalse(Schema::hasColumn('maps', 'is_city'));
    }

    public function test_guest_cannot_read_related_maps_or_change_exhaustion(): void
    {
        $city = $this->map('Miasto');
        auth()->logout();
        $this->getJson(route('maps.city.related', $city->id))->assertUnauthorized();
        $this->patchJson(route('maps.update.city', $city->id), ['is_city' => true])->assertUnauthorized();
        $this->patchJson(route('maps.bulk.city'), ['map_ids' => [$city->id], 'is_city' => true])->assertUnauthorized();
    }

    private function map(string $name, bool $isCity = false): Map
    {
        return Map::query()->create(['name' => $name, 'x' => 10, 'y' => 10, 'is_city' => $isCity]);
    }

    private function door(Map $from, Map $to, int $x = 4, int $y = 4): void
    {
        Door::query()->create(['map_id' => $from->id, 'go_map_id' => $to->id, 'x' => $x, 'y' => $y]);
    }
}

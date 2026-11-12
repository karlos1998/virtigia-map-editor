<?php

namespace Tests\Feature;

use App\Enums\MapGroup;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\User;
use App\Models\WorldTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MapGroupTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/remote/2026_11_12_220000_add_map_group_to_maps_table.php';

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => $connection]);
        DynamicModel::setGlobalConnection($connection);
        Cache::forget('respawn_points_data');

        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->boolean('is_city')->default(false);
            $table->foreignId('respawn_point_id')->nullable();
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

        (require database_path(self::MIGRATION))->up();
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_migration_puts_existing_maps_in_no_group(): void
    {
        DB::table('maps')->insert(['id' => 1, 'name' => 'Ithan']);

        $this->assertSame('no_group', DB::table('maps')->where('id', 1)->value('map_group'));
        $this->assertSame(MapGroup::NO_GROUP, Map::query()->find(1)->map_group);
    }

    public function test_every_map_group_has_a_polish_label(): void
    {
        $this->assertSame([
            ['label' => 'Bez grupy', 'value' => 'no_group'],
            ['label' => 'Mroczne jaskinie', 'value' => 'bad_caves'],
            ['label' => 'Jaskinie', 'value' => 'caves'],
            ['label' => 'Mroczne tereny', 'value' => 'bad_fields'],
            ['label' => 'Tereny otwarte', 'value' => 'fields'],
            ['label' => 'Wnętrza', 'value' => 'insides'],
            ['label' => 'Mroczne wnętrza', 'value' => 'bad_insides'],
            ['label' => 'Morze', 'value' => 'sea'],
            ['label' => 'Pustynia', 'value' => 'desert'],
        ], MapGroup::toDropdownList());
    }

    public function test_map_group_can_be_changed_from_the_editor(): void
    {
        $map = Map::query()->create(['name' => 'Mroczny przesmyk']);

        $this->actingAs($this->admin())
            ->withSession(['world' => 'retro'])
            ->patch(route('maps.update.group', $map), ['map_group' => 'bad_caves'])
            ->assertSuccessful();

        $this->assertSame(MapGroup::BAD_CAVES, $map->fresh()->map_group);
    }

    public function test_unknown_map_group_is_rejected(): void
    {
        $map = Map::query()->create(['name' => 'Mroczny przesmyk']);

        $this->actingAs($this->admin())
            ->withSession(['world' => 'retro'])
            ->patch(route('maps.update.group', $map), ['map_group' => 'disco'])
            ->assertSessionHasErrors('map_group');

        $this->assertSame(MapGroup::NO_GROUP, $map->fresh()->map_group);
    }

    public function test_map_list_can_be_filtered_by_map_group(): void
    {
        DB::table('maps')->insert([
            ['id' => 1, 'name' => 'Ithan', 'map_group' => 'fields'],
            ['id' => 2, 'name' => 'Jaskinia', 'map_group' => 'caves'],
            ['id' => 3, 'name' => 'Pustynia', 'map_group' => 'desert'],
        ]);

        $this->actingAs($this->admin())
            ->withSession(['world' => 'retro'])
            ->get(route('maps.index', ['map_group' => 'caves']))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Map/Index')
                ->has('maps.data', 1)
                ->where('maps.data.0.id', 2)
                ->where('maps.data.0.map_group', 'caves')
                ->where('filters.map_group', 'caves')
                ->has('mapGroupList', 9));
    }

    private function admin(): User
    {
        return User::factory()->create(['roles' => ['admin'], 'permissions' => ['world.write']]);
    }
}

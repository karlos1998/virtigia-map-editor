<?php

namespace Tests\Feature;

use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\User;
use App\Models\WorldTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MapInternalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = config('database.default');
        WorldTemplate::query()->where('slug', 'retro')->update(['connection_name' => $connection]);
        DynamicModel::setGlobalConnection($connection);

        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->nullable();
            $table->boolean('is_city')->default(false);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_migration_marks_the_game_master_headquarters_as_internal(): void
    {
        DB::table('maps')->insert([
            ['id' => 1, 'name' => 'Siedziba MG'],
            ['id' => 2, 'name' => 'Thuzal'],
        ]);

        (require database_path('migrations/remote/2026_11_10_200000_add_is_internal_to_maps_table.php'))->up();

        $this->assertSame([1 => 1, 2 => 0], DB::table('maps')->pluck('is_internal', 'id')->map(fn ($value) => (int) $value)->all());
    }

    public function test_map_can_be_marked_internal_from_the_editor(): void
    {
        (require database_path('migrations/remote/2026_11_10_200000_add_is_internal_to_maps_table.php'))->up();
        $map = Map::query()->create(['name' => 'Arena testowa']);

        $this->actingAs(User::factory()->create(['roles' => ['admin'], 'permissions' => ['world.write']]))
            ->withSession(['world' => 'retro'])
            ->patch(route('maps.update.internal', $map), ['is_internal' => true])
            ->assertSuccessful();

        $this->assertTrue($map->fresh()->is_internal);
    }
}

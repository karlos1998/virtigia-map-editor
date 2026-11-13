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

class MapBattleground3Test extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/remote/2026_11_13_114638_add_battleground3_to_maps_table.php';

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
            $table->string('battleground2')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_migration_assigns_modern_backgrounds_from_classic_filenames(): void
    {
        $classic = ['027N.jpg', '27n.jpg', '27nN.jpg', '007N.jpg', 'classic/004N.jpg', 'matchmaking.jpg', '36.jpg', '37N.jpg', '999.jpg', null];
        foreach ($classic as $index => $background) {
            DB::table('maps')->insert(['id' => $index + 1, 'name' => 'Test', 'battleground2' => $background]);
        }

        $migration = require database_path(self::MIGRATION);
        $migration->up();

        $this->assertSame(
            ['m4.jpg', 'm17.jpg', 'm17.jpg', 'm11.jpg', 'm13.jpg', 'm20.jpg', 'm24.jpg', 'm26.jpg', null, null],
            DB::table('maps')->orderBy('id')->pluck('battleground3')->all(),
        );
        $this->assertSame($classic, DB::table('maps')->orderBy('id')->pluck('battleground2')->all());

        $migration->down();
        $this->assertFalse(Schema::hasColumn('maps', 'battleground3'));
    }

    public function test_editor_saves_only_modern_backgrounds_from_the_catalog(): void
    {
        (require database_path(self::MIGRATION))->up();
        $map = Map::query()->create(['name' => 'Grota', 'battleground2' => '004N.jpg']);

        $this->actingAs(User::factory()->create(['roles' => ['admin'], 'permissions' => ['world.write']]))
            ->withSession(['world' => 'retro']);

        $url = route('maps.update.battleground3', $map);

        $this->from(route('maps.show', $map))
            ->patch($url, ['battleground3' => 'm27.jpg'])
            ->assertRedirect(route('maps.show', $map));

        $this->patch($url, ['battleground3' => 'm28.jpg'])->assertSessionHasErrors('battleground3');

        $map->refresh();
        $this->assertSame('m27.jpg', $map->battleground3);
        $this->assertSame('004N.jpg', $map->battleground2);
    }
}

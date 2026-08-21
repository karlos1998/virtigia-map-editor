<?php

namespace Tests\Feature;

use App\Models\DialogCounter;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\MapTrack;
use App\Services\MapTrackService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MapTrackServiceTest extends TestCase
{
    private string $databasePath;

    private string $defaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = database_path('testing-map-tracks.sqlite');
        File::delete($this->databasePath);
        File::put($this->databasePath, '');

        config()->set('database.connections.track_testing', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('track_testing');
        $this->defaultConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('track_testing');
        DynamicModel::setGlobalConnection('track_testing');
        activity()->disableLogging();

        Schema::create('dialog_counters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('scope')->default('character');
            $table->timestamps();
        });
        Schema::create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('x');
            $table->unsignedInteger('y');
            $table->text('col');
            $table->string('src');
            $table->timestamps();
        });

        $migration = require database_path('migrations/remote/2026_08_21_170000_create_map_tracks_tables.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        activity()->enableLogging();
        DynamicModel::clearGlobalConnection();
        DB::disconnect('track_testing');
        DB::setDefaultConnection($this->defaultConnection);
        File::delete($this->databasePath);

        parent::tearDown();
    }

    public function test_it_stores_checkpoint_tiles_and_maintains_a_contiguous_order(): void
    {
        $counter = DialogCounter::query()->create([
            'name' => 'Ukończone okrążenia',
            'scope' => 'character',
        ]);
        $firstMap = Map::query()->create([
            'name' => 'Start',
            'x' => 20,
            'y' => 20,
            'col' => str_repeat('0', 400),
            'src' => 'start.png',
        ]);
        $secondMap = Map::query()->create([
            'name' => 'Meta',
            'x' => 20,
            'y' => 20,
            'col' => str_repeat('0', 400),
            'src' => 'meta.png',
        ]);
        $track = MapTrack::query()->create([
            'name' => 'Dookoła krainy',
            'dialog_counter_id' => $counter->id,
            'enabled' => true,
        ]);

        $service = app(MapTrackService::class);
        $first = $service->createCheckpoint($track, [
            'name' => 'Brama startowa',
            'map_id' => $firstMap->id,
            'tiles' => [['x' => 2, 'y' => 4], ['x' => 3, 'y' => 4]],
        ]);
        $second = $service->createCheckpoint($track, [
            'name' => 'Meta',
            'map_id' => $secondMap->id,
            'tiles' => [['x' => 10, 'y' => 8]],
        ]);

        $this->assertSame(1, $first->sequence);
        $this->assertSame(2, $second->sequence);
        $this->assertSame([[2, 4], [3, 4]], $first->tiles()->get()->map(fn ($tile): array => [$tile->x, $tile->y])->all());

        $service->moveCheckpoint($track, $second, 'up');

        $this->assertSame(2, $first->fresh()->sequence);
        $this->assertSame(1, $second->fresh()->sequence);

        $service->deleteCheckpoint($track, $second->fresh());

        $this->assertSame([1], $track->checkpoints()->pluck('sequence')->all());
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MapIndexAdvancedFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();

        config()->set('database.connections.retro', [
            'driver' => 'sqlite',
            'database' => database_path('testing-retro.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (! file_exists(database_path('testing-retro.sqlite'))) {
            touch(database_path('testing-retro.sqlite'));
        }

        Cache::forget('respawn_points_data');

        Schema::connection('retro')->disableForeignKeyConstraints();
        Schema::connection('retro')->dropIfExists('respawn_points');
        Schema::connection('retro')->dropIfExists('maps');

        Schema::connection('retro')->create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->unsignedInteger('x')->default(1);
            $table->unsignedInteger('y')->default(1);
            $table->text('col')->nullable();
            $table->text('water')->nullable();
            $table->integer('pvp')->nullable();
            $table->text('battleground')->nullable();
            $table->text('battleground2')->nullable();
            $table->foreignId('respawn_point_id')->nullable();
            $table->boolean('is_teleport_locked')->default(false);
            $table->timestamps();
        });

        Schema::connection('retro')->create('respawn_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('map_id')->nullable();
            $table->integer('x');
            $table->integer('y');
            $table->integer('max_steps');
            $table->timestamps();
        });

        DB::connection('retro')->table('maps')->insert([
            [
                'id' => 1,
                'name' => 'Gotowe tła',
                'src' => 'maps/ready.png',
                'x' => 10,
                'y' => 10,
                'col' => null,
                'water' => null,
                'pvp' => 0,
                'battleground' => 'forest.jpg',
                'battleground2' => '007N.jpg',
                'respawn_point_id' => null,
                'is_teleport_locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Brak pierwszego tła',
                'src' => 'maps/missing-first.png',
                'x' => 10,
                'y' => 10,
                'col' => null,
                'water' => null,
                'pvp' => 0,
                'battleground' => null,
                'battleground2' => '007N.jpg',
                'respawn_point_id' => null,
                'is_teleport_locked' => false,
                'created_at' => now()->subSecond(),
                'updated_at' => now()->subSecond(),
            ],
            [
                'id' => 3,
                'name' => 'Brak drugiego tła',
                'src' => 'maps/missing-second.png',
                'x' => 10,
                'y' => 10,
                'col' => null,
                'water' => null,
                'pvp' => 0,
                'battleground' => 'forest.jpg',
                'battleground2' => '',
                'respawn_point_id' => null,
                'is_teleport_locked' => false,
                'created_at' => now()->subSeconds(2),
                'updated_at' => now()->subSeconds(2),
            ],
        ]);

        Schema::connection('retro')->enableForeignKeyConstraints();
    }

    public function test_it_filters_maps_without_any_battleground_assigned(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('maps.index', [
                'missing_battleground' => '1',
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Map/Index')
            ->has('maps.data', 2)
            ->where('maps.data.0.id', 2)
            ->where('maps.data.1.id', 3)
            ->where('filters.missing_battleground', true));
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'map-filter',
            'name' => 'Map Filter',
            'email' => 'map-filter@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}

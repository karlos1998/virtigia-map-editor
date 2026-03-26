<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorldInfoBulkGuaranteedLootTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
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

        Schema::connection('retro')->dropIfExists('base_npcs');

        Schema::connection('retro')->create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('src')->default('retro/example.gif');
            $table->integer('lvl')->default(0);
            $table->boolean('guaranteed_loot')->default(false);
        });
    }

    public function test_it_returns_preview_for_bulk_guaranteed_loot_changes(): void
    {
        $this->seedBaseNpcs();

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->post(route('world-info.base-npcs.guaranteed-loot.preview'), [
                'level' => 20,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('count', 16)
            ->assertJsonCount(15, 'examples')
            ->assertJsonPath('examples.0.id', 1)
            ->assertJsonPath('examples.14.id', 15);
    }

    public function test_it_updates_only_matching_base_npcs(): void
    {
        $this->seedBaseNpcs();

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->patch(route('world-info.base-npcs.guaranteed-loot.apply'), [
                'level' => 20,
            ]);

        $response->assertRedirect();

        $this->assertSame(16, DB::connection('retro')->table('base_npcs')->where('guaranteed_loot', true)->count());
        $this->assertDatabaseHas('base_npcs', [
            'id' => 17,
            'guaranteed_loot' => false,
        ], 'retro');
        $this->assertDatabaseHas('base_npcs', [
            'id' => 18,
            'guaranteed_loot' => true,
        ], 'retro');
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'world-editor',
            'name' => 'World Editor',
            'email' => 'world-editor@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }

    private function seedBaseNpcs(): void
    {
        $records = [];

        for ($id = 1; $id <= 16; $id++) {
            $records[] = [
                'id' => $id,
                'name' => "Npc {$id}",
                'src' => "retro/npc-{$id}.gif",
                'lvl' => $id,
                'guaranteed_loot' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $records[] = [
            'id' => 17,
            'name' => 'Npc 21',
            'src' => 'retro/npc-17.gif',
            'lvl' => 21,
            'guaranteed_loot' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $records[] = [
            'id' => 18,
            'name' => 'Npc guaranteed',
            'src' => 'retro/npc-18.gif',
            'lvl' => 10,
            'guaranteed_loot' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::connection('retro')->table('base_npcs')->insert($records);
    }
}

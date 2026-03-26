<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UpdateBaseNpcDropChancesTest extends TestCase
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
            $table->string('name');
            $table->string('src')->default('retro/example.gif');
            $table->integer('lvl')->default(0);
            $table->string('rank')->default('NORMAL');
            $table->string('category')->default('MOB');
            $table->string('profession')->default('w');
            $table->boolean('is_aggressive')->default(false);
            $table->boolean('divine_intervention')->nullable();
            $table->boolean('guaranteed_loot')->default(false);
            $table->text('drop_chances')->nullable();
            $table->unsignedInteger('min_respawn_time')->nullable();
            $table->unsignedInteger('max_respawn_time')->nullable();
        });

        DB::connection('retro')->table('base_npcs')->insert([
            'id' => 123,
            'name' => 'Npc testowy',
            'src' => 'retro/test.gif',
            'lvl' => 50,
            'rank' => 'NORMAL',
            'category' => 'MOB',
            'profession' => 'w',
            'is_aggressive' => false,
            'divine_intervention' => null,
            'guaranteed_loot' => false,
            'drop_chances' => null,
            'min_respawn_time' => null,
            'max_respawn_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_updates_base_npc_drop_chances(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->patch(route('base-npcs.update', ['baseNpc' => 123]), [
                'name' => 'Npc testowy',
                'lvl' => 50,
                'rank' => 'NORMAL',
                'category' => 'MOB',
                'profession' => 'w',
                'is_aggressive' => false,
                'divine_intervention' => null,
                'drop_chances' => [0.7, 0.15, 0.1, 0.04, 0.01],
                'min_respawn_time' => null,
                'max_respawn_time' => null,
            ]);

        $response->assertOk();

        $this->assertSame(
            '[0.7,0.15,0.1,0.04,0.01]',
            DB::connection('retro')->table('base_npcs')->where('id', 123)->value('drop_chances')
        );
    }

    public function test_it_allows_resetting_drop_chances_to_null(): void
    {
        DB::connection('retro')->table('base_npcs')->where('id', 123)->update([
            'drop_chances' => '[0.7,0.15,0.1,0.04,0.01]',
        ]);

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->patch(route('base-npcs.update', ['baseNpc' => 123]), [
                'name' => 'Npc testowy',
                'lvl' => 50,
                'rank' => 'NORMAL',
                'category' => 'MOB',
                'profession' => 'w',
                'is_aggressive' => false,
                'divine_intervention' => null,
                'drop_chances' => null,
                'min_respawn_time' => null,
                'max_respawn_time' => null,
            ]);

        $response->assertOk();

        $this->assertNull(
            DB::connection('retro')->table('base_npcs')->where('id', 123)->value('drop_chances')
        );
    }

    public function test_it_rejects_drop_chances_when_sum_is_not_equal_to_one(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->patch(route('base-npcs.update', ['baseNpc' => 123]), [
                'name' => 'Npc testowy',
                'lvl' => 50,
                'rank' => 'NORMAL',
                'category' => 'MOB',
                'profession' => 'w',
                'is_aggressive' => false,
                'divine_intervention' => null,
                'drop_chances' => [0.5, 0.2, 0.1, 0.04, 0.01],
                'min_respawn_time' => null,
                'max_respawn_time' => null,
            ]);

        $response->assertSessionHasErrors('drop_chances');

        $this->assertNull(
            DB::connection('retro')->table('base_npcs')->where('id', 123)->value('drop_chances')
        );
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'base-npc-editor',
            'name' => 'Base NPC Editor',
            'email' => 'base-npc-editor@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}

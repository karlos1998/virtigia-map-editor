<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuestStepDescriptionOptionalTest extends TestCase
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

        Schema::connection('retro')->dropIfExists('quest_step_auto_progress_mobs');
        Schema::connection('retro')->dropIfExists('quest_step_auto_progresses');
        Schema::connection('retro')->dropIfExists('quest_steps');
        Schema::connection('retro')->dropIfExists('quests');

        Schema::connection('retro')->create('quests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('retro')->create('quest_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quest_id')->constrained('quests')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('visible_in_quest_list')->default(true);
            $table->boolean('auto_advance_next_day')->default(false);
            $table->foreignId('auto_advance_to_step_id')->nullable()->constrained('quest_steps')->nullOnDelete();
            $table->timestamps();
        });

        Schema::connection('retro')->create('quest_step_auto_progresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quest_step_id')->constrained('quest_steps')->cascadeOnDelete();
            $table->string('type');
            $table->integer('time_seconds')->nullable();
            $table->timestamps();
        });

        Schema::connection('retro')->create('quest_step_auto_progress_mobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quest_step_auto_progress_id')->constrained('quest_step_auto_progresses')->cascadeOnDelete();
            $table->foreignId('base_npc_id')->nullable();
            $table->foreignId('mob_species_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function test_it_can_create_a_quest_step_without_description(): void
    {
        DB::connection('retro')->table('quests')->insert([
            'id' => 1,
            'name' => 'Test quest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->post(route('quests.steps.store', ['quest' => 1]), [
                'name' => 'Pierwszy krok',
            ])
            ->assertOk();

        $this->assertDatabaseHas('quest_steps', [
            'quest_id' => 1,
            'name' => 'Pierwszy krok',
            'description' => null,
        ], 'retro');
    }

    public function test_it_can_update_a_quest_step_with_null_description(): void
    {
        DB::connection('retro')->table('quests')->insert([
            'id' => 1,
            'name' => 'Test quest',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('retro')->table('quest_steps')->insert([
            'id' => 1,
            'quest_id' => 1,
            'name' => 'Pierwszy krok',
            'description' => 'Stary opis',
            'visible_in_quest_list' => true,
            'auto_advance_next_day' => false,
            'auto_advance_to_step_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->patch(route('quests.steps.update', ['quest' => 1, 'step' => 1]), [
                'name' => 'Pierwszy krok',
                'description' => null,
                'visible_in_quest_list' => true,
                'auto_progress' => false,
                'auto_advance_next_day' => false,
                'auto_advance_to_step_id' => null,
            ])
            ->assertOk();

        $this->assertDatabaseHas('quest_steps', [
            'id' => 1,
            'description' => null,
        ], 'retro');
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => fake()->unique()->userName(),
            'name' => 'Quest Editor',
            'email' => fake()->unique()->safeEmail(),
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}

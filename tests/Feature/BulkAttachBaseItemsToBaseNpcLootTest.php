<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkAttachBaseItemsToBaseNpcLootTest extends TestCase
{
    use RefreshDatabase;

    private string $worldConnection;

    protected function setUp(): void
    {
        if ((getenv('DB_CONNECTION') ?: 'sqlite') === 'sqlite') {
            $testingDatabasePath = dirname(__DIR__, 2).'/database/testing.sqlite';

            if (! file_exists($testingDatabasePath)) {
                touch($testingDatabasePath);
            }

            putenv("DB_DATABASE={$testingDatabasePath}");
            $_ENV['DB_DATABASE'] = $testingDatabasePath;
            $_SERVER['DB_DATABASE'] = $testingDatabasePath;
        }

        parent::setUp();

        $this->worldConnection = 'retro';

        config()->set('database.connections.retro', [
            'driver' => 'sqlite',
            'database' => database_path('testing-bulk-loot-retro.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (! file_exists(database_path('testing-bulk-loot-retro.sqlite'))) {
            touch(database_path('testing-bulk-loot-retro.sqlite'));
        }

        Schema::connection($this->worldConnection)->dropIfExists('base_npc_loots');
        Schema::connection($this->worldConnection)->dropIfExists('base_items');
        Schema::connection($this->worldConnection)->dropIfExists('base_npcs');

        Schema::connection($this->worldConnection)->create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->text('stats')->default('');
            $table->unsignedInteger('cl')->default(0);
            $table->unsignedInteger('pr')->default(0);
            $table->boolean('edited_manually')->default(false);
            $table->json('attributes')->nullable();
            $table->json('attribute_points')->nullable();
            $table->json('manual_attribute_points')->nullable();
            $table->json('reverse_attributes')->nullable();
            $table->string('rarity')->nullable();
            $table->string('category')->nullable();
            $table->boolean('suggest_destroy_on_level_gap')->default(true);
            $table->unsignedInteger('price')->nullable();
            $table->string('currency')->nullable();
            $table->unsignedInteger('specific_currency_price')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::connection($this->worldConnection)->create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->unsignedInteger('lvl')->default(0);
            $table->unsignedInteger('type')->default(0);
            $table->unsignedInteger('wt')->default(0);
            $table->string('rank')->default('NORMAL');
            $table->string('category')->default('MOB');
            $table->string('profession')->default('w');
            $table->boolean('is_aggressive')->default(false);
            $table->boolean('divine_intervention')->nullable();
            $table->boolean('guaranteed_loot')->default(false);
            $table->json('drop_chances')->nullable();
            $table->unsignedInteger('min_respawn_time')->nullable();
            $table->unsignedInteger('max_respawn_time')->nullable();
            $table->timestamps();
        });

        Schema::connection($this->worldConnection)->create('base_npc_loots', function (Blueprint $table): void {
            $table->boolean('destroy_on_level_gap')->default(true);
            $table->id();
            $table->foreignId('base_npc_id');
            $table->foreignId('base_item_id');
            $table->timestamps();
        });
    }

    public function test_it_attaches_selected_items_as_base_npc_loot_without_duplicates(): void
    {
        $this->seedBaseNpc();
        $this->seedBaseItems();

        DB::connection($this->worldConnection)->table('base_npc_loots')->insert([
            'base_npc_id' => 10,
            'base_item_id' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => $this->worldConnection])
            ->post(route('base-items.bulk.base-npc-loots.attach'), [
                'base_npc_id' => 10,
                'item_ids' => [1, 2, 3],
            ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Dodano 2 przedmiotów jako loot. Pominięto 1 już przypisanych.');

        $this->assertSame(
            [1, 2, 3],
            DB::connection($this->worldConnection)
                ->table('base_npc_loots')
                ->where('base_npc_id', 10)
                ->orderBy('base_item_id')
                ->pluck('base_item_id')
                ->all()
        );

        $this->assertSame(
            1,
            DB::connection($this->worldConnection)
                ->table('base_npc_loots')
                ->where('base_npc_id', 10)
                ->where('base_item_id', 2)
                ->count()
        );
    }

    public function test_new_loot_uses_npc_and_item_defaults_and_can_be_overridden(): void
    {
        $this->seedBaseNpc();
        $this->seedBaseItems();

        $world = DB::connection($this->worldConnection);
        $world->table('base_npcs')->where('id', 10)->update(['lvl' => 15]);
        $world->table('base_items')->where('id', 2)->update(['suggest_destroy_on_level_gap' => false]);

        $this->actingAs($this->makeUser())->withSession(['world' => $this->worldConnection]);

        $this->post(route('base-npcs.loots.attach', ['baseNpc' => 10]), ['baseItemId' => 1])->assertRedirect();
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 1, 'destroy_on_level_gap' => false], $this->worldConnection);

        $this->patch(route('base-npcs.loots.update', ['baseNpc' => 10, 'loot' => 1]), [
            'destroy_on_level_gap' => true,
        ])->assertRedirect();
        $this->post(route('base-npcs.loots.attach', ['baseNpc' => 10]), ['baseItemId' => 1])->assertRedirect();
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 1, 'destroy_on_level_gap' => true], $this->worldConnection);

        $world->table('base_npcs')->where('id', 10)->update(['lvl' => 16]);
        $this->post(route('base-items.bulk.base-npc-loots.attach'), ['base_npc_id' => 10, 'item_ids' => [2, 3]])->assertRedirect();
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 2, 'destroy_on_level_gap' => false], $this->worldConnection);
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 3, 'destroy_on_level_gap' => true], $this->worldConnection);
    }

    public function test_high_level_npcs_get_loot_that_is_never_destroyed(): void
    {
        $this->seedBaseNpc();
        $this->seedBaseItems();
        $world = DB::connection($this->worldConnection);
        $this->actingAs($this->makeUser())->withSession(['world' => $this->worldConnection]);

        $world->table('base_npcs')->where('id', 10)->update(['lvl' => 260, 'rank' => 'NORMAL']);
        $this->post(route('base-npcs.loots.attach', ['baseNpc' => 10]), ['baseItemId' => 1])->assertRedirect();
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 1, 'destroy_on_level_gap' => true], $this->worldConnection);

        $world->table('base_npcs')->where('id', 10)->update(['lvl' => 270]);
        $this->post(route('base-npcs.loots.attach', ['baseNpc' => 10]), ['baseItemId' => 3])->assertRedirect();
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 3, 'destroy_on_level_gap' => false], $this->worldConnection);

        $world->table('base_npcs')->where('id', 10)->update(['lvl' => 250, 'rank' => 'HERO']);
        $this->post(route('base-npcs.loots.attach', ['baseNpc' => 10]), ['baseItemId' => 2])->assertRedirect();
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 10, 'base_item_id' => 2, 'destroy_on_level_gap' => false], $this->worldConnection);
    }

    public function test_migration_switches_off_level_gap_rules_for_high_level_npcs(): void
    {
        $this->seedBaseItems();
        $world = DB::connection($this->worldConnection);
        $world->table('base_npcs')->insert([
            ['id' => 21, 'name' => 'Heros 249', 'lvl' => 249, 'rank' => 'HERO', 'divine_intervention' => null],
            ['id' => 22, 'name' => 'Heros 250', 'lvl' => 250, 'rank' => 'HERO', 'divine_intervention' => null],
            ['id' => 23, 'name' => 'Elita 250', 'lvl' => 250, 'rank' => 'ELITE', 'divine_intervention' => true],
            ['id' => 24, 'name' => 'Zwykły 260', 'lvl' => 260, 'rank' => 'NORMAL', 'divine_intervention' => null],
            ['id' => 25, 'name' => 'Zwykły 270', 'lvl' => 270, 'rank' => 'NORMAL', 'divine_intervention' => null],
        ]);
        foreach ([21, 22, 23, 24, 25] as $npcId) {
            $world->table('base_npc_loots')->insert(['base_npc_id' => $npcId, 'base_item_id' => 1, 'destroy_on_level_gap' => true]);
        }

        $this->artisan('migrate', [
            '--database' => $this->worldConnection,
            '--path' => 'database/migrations/remote/2026_11_11_163429_disable_level_gap_rules_for_high_level_npcs.php',
        ])->assertSuccessful();

        $this->assertSame(
            [21 => null, 22 => 0, 23 => 0, 24 => 0, 25 => 0],
            $world->table('base_npcs')->whereIn('id', [21, 22, 23, 24, 25])->orderBy('id')->pluck('divine_intervention', 'id')->map(fn ($value) => $value === null ? null : (int) $value)->all(),
        );
        $this->assertSame(
            [21 => 1, 22 => 0, 23 => 0, 24 => 1, 25 => 0],
            $world->table('base_npc_loots')->orderBy('base_npc_id')->pluck('destroy_on_level_gap', 'base_npc_id')->map(fn ($value) => (int) $value)->all(),
        );
    }

    public function test_new_high_level_npcs_start_without_divine_intervention(): void
    {
        \App\Models\DynamicModel::setGlobalConnection($this->worldConnection);
        Schema::connection($this->worldConnection)->table('base_npcs', function (Blueprint $table): void {
            $table->integer('draw_offset_x')->default(0);
            $table->integer('draw_offset_y')->default(0);
            $table->string('layer_depth')->nullable();
        });
        $high = \App\Models\BaseNpc::create(['name' => 'Smok', 'src' => 'x.gif', 'lvl' => 300, 'rank' => 'TITAN', 'category' => 'MOB', 'profession' => 'w']);
        $low = \App\Models\BaseNpc::create(['name' => 'Wilk', 'src' => 'x.gif', 'lvl' => 20, 'rank' => 'HERO', 'category' => 'MOB', 'profession' => 'w']);
        $forced = \App\Models\BaseNpc::create(['name' => 'Smok 2', 'src' => 'x.gif', 'lvl' => 300, 'rank' => 'TITAN', 'category' => 'MOB', 'profession' => 'w', 'divine_intervention' => true]);

        $this->assertFalse($high->fresh()->divine_intervention);
        $this->assertNull($low->fresh()->divine_intervention);
        $this->assertTrue($forced->fresh()->divine_intervention);
        \App\Models\DynamicModel::clearGlobalConnection();
    }

    public function test_migration_protects_early_normal_mobs_and_special_item_categories(): void
    {
        $this->seedBaseNpc();
        $this->seedBaseItems();
        $schema = Schema::connection($this->worldConnection);
        $schema->dropIfExists('migrations');
        $schema->table('base_items', fn (Blueprint $table) => $table->dropColumn('suggest_destroy_on_level_gap'));
        $schema->table('base_npc_loots', fn (Blueprint $table) => $table->dropColumn('destroy_on_level_gap'));
        $world = DB::connection($this->worldConnection);
        $world->table('base_items')->where('id', 2)->update(['category' => 'neutrals']);
        $world->table('base_items')->where('id', 3)->update(['category' => 'quests']);
        $world->table('base_npcs')->insert([
            ['id' => 11, 'name' => 'Zwykły 15', 'lvl' => 15, 'rank' => 'NORMAL'],
            ['id' => 12, 'name' => 'Zwykły 16', 'lvl' => 16, 'rank' => 'NORMAL'],
            ['id' => 13, 'name' => 'Elita 15', 'lvl' => 15, 'rank' => 'ELITE'],
        ]);
        $world->table('base_npc_loots')->insert([
            ['base_npc_id' => 11, 'base_item_id' => 1],
            ['base_npc_id' => 12, 'base_item_id' => 1],
            ['base_npc_id' => 13, 'base_item_id' => 1],
            ['base_npc_id' => 12, 'base_item_id' => 2],
            ['base_npc_id' => 12, 'base_item_id' => 3],
        ]);

        $this->artisan('migrate', [
            '--database' => $this->worldConnection,
            '--path' => 'database/migrations/remote/2026_11_09_150000_add_level_gap_rules_to_npc_loot.php',
        ])->assertSuccessful();

        $this->assertSame([1, 0, 0], $world->table('base_items')->orderBy('id')->pluck('suggest_destroy_on_level_gap')->all());
        $this->assertSame([0, 1, 1, 0, 0], $world->table('base_npc_loots')->orderBy('id')->pluck('destroy_on_level_gap')->all());
    }

    public function test_it_requires_base_npc(): void
    {
        $this->seedBaseItems();

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => $this->worldConnection])
            ->post(route('base-items.bulk.base-npc-loots.attach'), [
                'base_npc_id' => null,
                'item_ids' => [1],
            ]);

        $response->assertSessionHasErrors('base_npc_id');
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'base-item-bulk-loot-editor',
            'name' => 'Base Item Bulk Loot Editor',
            'email' => 'base-item-bulk-loot-editor@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }

    private function seedBaseNpc(): void
    {
        DB::connection($this->worldConnection)->table('base_npcs')->insert([
            'id' => 10,
            'name' => 'Testowy potwór',
            'src' => 'retro/npc.gif',
            'lvl' => 30,
            'type' => 0,
            'wt' => 0,
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

    private function seedBaseItems(): void
    {
        DB::connection($this->worldConnection)->table('base_items')->insert([
            $this->baseItemRecord(1, 'Pierwszy loot'),
            $this->baseItemRecord(2, 'Istniejący loot'),
            $this->baseItemRecord(3, 'Trzeci loot'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseItemRecord(int $id, string $name): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'src' => "items/{$id}.gif",
            'stats' => '',
            'cl' => 0,
            'pr' => 0,
            'edited_manually' => false,
            'attributes' => null,
            'attribute_points' => null,
            'manual_attribute_points' => null,
            'reverse_attributes' => null,
            'rarity' => null,
            'category' => null,
            'price' => null,
            'currency' => null,
            'specific_currency_price' => null,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

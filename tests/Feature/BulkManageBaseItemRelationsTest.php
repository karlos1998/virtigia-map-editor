<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkManageBaseItemRelationsTest extends TestCase
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
            'database' => database_path('testing-bulk-base-item-relations-retro.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (! file_exists(database_path('testing-bulk-base-item-relations-retro.sqlite'))) {
            touch(database_path('testing-bulk-base-item-relations-retro.sqlite'));
        }

        $this->createWorldTables();
        $this->seedWorldData();
    }

    public function test_it_attaches_items_to_the_first_available_shop_positions_and_skips_existing_relations(): void
    {
        DB::connection($this->worldConnection)->table('shop_items')->insert([
            ['shop_id' => 1, 'item_id' => 1, 'position' => 3],
            ['shop_id' => 1, 'item_id' => 4, 'position' => 4],
        ]);

        $response = $this->worldRequest()->post(route('base-items.bulk.shop-items.attach'), [
            'shop_id' => 1,
            'start_position' => 3,
            'item_ids' => [1, 2, 3],
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('shop_items', ['shop_id' => 1, 'item_id' => 2, 'position' => 5], $this->worldConnection);
        $this->assertDatabaseHas('shop_items', ['shop_id' => 1, 'item_id' => 3, 'position' => 6], $this->worldConnection);
        $this->assertSame(4, DB::connection($this->worldConnection)->table('shop_items')->count());
    }

    public function test_it_does_not_partially_attach_items_when_the_shop_has_too_few_positions(): void
    {
        DB::connection($this->worldConnection)->table('shop_items')->insert([
            ['shop_id' => 1, 'item_id' => 4, 'position' => 79],
        ]);

        $response = $this->worldRequest()->post(route('base-items.bulk.shop-items.attach'), [
            'shop_id' => 1,
            'start_position' => 78,
            'item_ids' => [1, 2],
        ]);

        $response->assertSessionHasErrors('start_position');
        $this->assertDatabaseMissing('shop_items', ['shop_id' => 1, 'item_id' => 1], $this->worldConnection);
        $this->assertDatabaseMissing('shop_items', ['shop_id' => 1, 'item_id' => 2], $this->worldConnection);
    }

    public function test_it_detaches_only_selected_items_from_a_shop(): void
    {
        DB::connection($this->worldConnection)->table('shop_items')->insert([
            ['shop_id' => 1, 'item_id' => 1, 'position' => 1],
            ['shop_id' => 1, 'item_id' => 2, 'position' => 2],
            ['shop_id' => 1, 'item_id' => 3, 'position' => 3],
        ]);

        $response = $this->worldRequest()->delete(route('base-items.bulk.relations.detach'), [
            'target_type' => 'shop',
            'target_id' => 1,
            'item_ids' => [1, 2, 4],
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('shop_items', ['shop_id' => 1, 'item_id' => 1], $this->worldConnection);
        $this->assertDatabaseMissing('shop_items', ['shop_id' => 1, 'item_id' => 2], $this->worldConnection);
        $this->assertDatabaseHas('shop_items', ['shop_id' => 1, 'item_id' => 3], $this->worldConnection);
    }

    public function test_it_detaches_only_selected_loots_from_a_base_npc(): void
    {
        DB::connection($this->worldConnection)->table('base_npc_loots')->insert([
            ['base_npc_id' => 1, 'base_item_id' => 1],
            ['base_npc_id' => 1, 'base_item_id' => 2],
            ['base_npc_id' => 1, 'base_item_id' => 3],
        ]);

        $response = $this->worldRequest()->delete(route('base-items.bulk.relations.detach'), [
            'target_type' => 'base_npc',
            'target_id' => 1,
            'item_ids' => [1, 3],
        ]);

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('base_npc_loots', ['base_npc_id' => 1, 'base_item_id' => 1], $this->worldConnection);
        $this->assertDatabaseMissing('base_npc_loots', ['base_npc_id' => 1, 'base_item_id' => 3], $this->worldConnection);
        $this->assertDatabaseHas('base_npc_loots', ['base_npc_id' => 1, 'base_item_id' => 2], $this->worldConnection);
    }

    private function worldRequest(): self
    {
        return $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => $this->worldConnection]);
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => fake()->unique()->userName(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }

    private function createWorldTables(): void
    {
        $schema = Schema::connection($this->worldConnection);
        $schema->dropIfExists('base_npc_loots');
        $schema->dropIfExists('shop_items');
        $schema->dropIfExists('base_npcs');
        $schema->dropIfExists('shops');
        $schema->dropIfExists('base_items');

        $schema->create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->softDeletes();
            $table->timestamps();
        });
        $schema->create('shops', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        $schema->create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        $schema->create('shop_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('base_items')->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->unique(['shop_id', 'position']);
            $table->timestamps();
        });
        $schema->create('base_npc_loots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_npc_id')->constrained('base_npcs')->cascadeOnDelete();
            $table->foreignId('base_item_id')->constrained('base_items')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    private function seedWorldData(): void
    {
        DB::connection($this->worldConnection)->table('base_items')->insert(
            collect(range(1, 4))->map(fn (int $id): array => [
                'id' => $id,
                'name' => "Przedmiot {$id}",
                'created_at' => now(),
                'updated_at' => now(),
            ])->all()
        );
        DB::connection($this->worldConnection)->table('shops')->insert([
            'id' => 1,
            'name' => 'Sklep testowy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection($this->worldConnection)->table('base_npcs')->insert([
            'id' => 1,
            'name' => 'NPC testowy',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

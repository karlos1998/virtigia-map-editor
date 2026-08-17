<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkUpdateBaseItemsTest extends TestCase
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
            'database' => database_path('testing-bulk-base-items-retro.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (! file_exists(database_path('testing-bulk-base-items-retro.sqlite'))) {
            touch(database_path('testing-bulk-base-items-retro.sqlite'));
        }

        Schema::connection($this->worldConnection)->dropIfExists('base_items');
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
            $table->unsignedInteger('price')->nullable();
            $table->string('currency')->nullable();
            $table->unsignedInteger('specific_currency_price')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $this->seedBaseItems();
    }

    public function test_it_sets_one_binding_mode_and_preserves_other_attributes(): void
    {
        DB::connection($this->worldConnection)
            ->table('base_items')
            ->where('id', 1)
            ->update([
                'attributes' => json_encode([
                    'description' => 'Opis przedmiotu',
                    'isBoundToOwner' => true,
                    'isBindsAfterEquip' => true,
                ]),
            ]);

        $response = $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'binding',
            'value' => 'isPermanentlyBounded',
            'prices' => [],
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('success', 'Zaktualizowano 2 przedmiotów.');

        foreach ([1, 2] as $itemId) {
            $attributes = $this->baseItemAttributes($itemId);

            $this->assertTrue($attributes['isPermanentlyBounded']);
            $this->assertArrayNotHasKey('isBoundToOwner', $attributes);
            $this->assertArrayNotHasKey('isBindsAfterEquip', $attributes);
        }

        $this->assertSame('Opis przedmiotu', $this->baseItemAttributes(1)['description']);
    }

    public function test_it_sets_rarity_and_uses_individually_recalculated_prices(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'rarity',
            'value' => 'heroic',
            'prices' => [
                ['item_id' => 1, 'price' => 1200],
                ['item_id' => 2, 'price' => 3400],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('base_items', [
            'id' => 1,
            'rarity' => 'heroic',
            'price' => 1200,
            'edited_manually' => true,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 2,
            'rarity' => 'heroic',
            'price' => 3400,
            'edited_manually' => true,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 3,
            'rarity' => 'common',
            'price' => 300,
            'edited_manually' => false,
        ], $this->worldConnection);
    }

    public function test_it_sets_currency_with_optional_price_recalculation(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 3],
            'operation' => 'currency',
            'value' => 'dragonTear',
            'prices' => [
                ['item_id' => 1, 'price' => 12],
                ['item_id' => 3, 'price' => 34],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('base_items', [
            'id' => 1,
            'currency' => 'dragonTear',
            'price' => 12,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 3,
            'currency' => 'dragonTear',
            'price' => 34,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 2,
            'currency' => 'gold',
            'price' => 200,
        ], $this->worldConnection);
    }

    public function test_it_updates_only_prices_for_the_selected_items(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 3],
            'operation' => 'price',
            'value' => null,
            'prices' => [
                ['item_id' => 1, 'price' => 1500],
                ['item_id' => 3, 'price' => 3500],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('base_items', ['id' => 1, 'price' => 1500], $this->worldConnection);
        $this->assertDatabaseHas('base_items', ['id' => 2, 'price' => 200], $this->worldConnection);
        $this->assertDatabaseHas('base_items', ['id' => 3, 'price' => 3500], $this->worldConnection);
    }

    public function test_it_rejects_a_price_for_an_item_outside_the_selection(): void
    {
        $response = $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'price',
            'value' => null,
            'prices' => [
                ['item_id' => 2, 'price' => 9999],
            ],
        ]);

        $response->assertSessionHasErrors('prices.0.item_id');
        $this->assertDatabaseHas('base_items', ['id' => 2, 'price' => 200], $this->worldConnection);
    }

    public function test_it_requires_a_recalculated_price_for_every_selected_item(): void
    {
        $response = $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'price',
            'value' => null,
            'prices' => [
                ['item_id' => 1, 'price' => 1500],
            ],
        ]);

        $response->assertSessionHasErrors('prices');
        $this->assertDatabaseHas('base_items', ['id' => 1, 'price' => 100], $this->worldConnection);
        $this->assertDatabaseHas('base_items', ['id' => 2, 'price' => 200], $this->worldConnection);
    }

    private function bulkUpdate(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => $this->worldConnection])
            ->patch(route('base-items.bulk.properties.update'), $payload);
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

    private function seedBaseItems(): void
    {
        DB::connection($this->worldConnection)->table('base_items')->insert([
            $this->baseItemRecord(1),
            $this->baseItemRecord(2),
            $this->baseItemRecord(3),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function baseItemRecord(int $id): array
    {
        return [
            'id' => $id,
            'name' => "Przedmiot {$id}",
            'src' => "items/{$id}.gif",
            'stats' => '',
            'cl' => 0,
            'pr' => 0,
            'edited_manually' => false,
            'attributes' => json_encode(['needLevel' => $id * 10]),
            'attribute_points' => json_encode(['strength' => $id]),
            'manual_attribute_points' => null,
            'reverse_attributes' => null,
            'rarity' => 'common',
            'category' => 'oneHanded',
            'price' => $id * 100,
            'currency' => 'gold',
            'specific_currency_price' => null,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseItemAttributes(int $itemId): array
    {
        $attributes = DB::connection($this->worldConnection)
            ->table('base_items')
            ->where('id', $itemId)
            ->value('attributes');

        return json_decode((string) $attributes, true, flags: JSON_THROW_ON_ERROR);
    }
}

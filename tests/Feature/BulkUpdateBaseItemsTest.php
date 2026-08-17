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
            'enabled' => null,
            'attribute_key' => null,
            'attribute_paths' => [],
            'name_mode' => null,
            'search_phrase' => null,
            'replacement_phrase' => null,
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

    public function test_it_removes_binding_and_can_set_or_unset_a_boolean_attribute(): void
    {
        DB::connection($this->worldConnection)->table('base_items')->where('id', 1)->update([
            'attributes' => json_encode([
                'needLevel' => 10,
                'isBoundToOwner' => true,
                'openAuction' => true,
            ]),
        ]);

        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'binding',
            'value' => 'none',
        ])->assertRedirect();
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'boolean_attribute',
            'value' => 'openAuction',
            'enabled' => false,
        ])->assertRedirect();

        $this->assertArrayNotHasKey('isBoundToOwner', $this->baseItemAttributes(1));
        $this->assertArrayNotHasKey('openAuction', $this->baseItemAttributes(1));
        $this->assertArrayNotHasKey('openAuction', $this->baseItemAttributes(2));
    }

    public function test_it_sets_and_clears_specific_currency_price(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'specific_currency_price',
            'value' => 125,
        ])->assertRedirect();
        $this->assertDatabaseHas('base_items', ['id' => 1, 'specific_currency_price' => 125], $this->worldConnection);

        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'specific_currency_price',
            'value' => null,
        ])->assertRedirect();
        $this->assertDatabaseHas('base_items', ['id' => 1, 'specific_currency_price' => null], $this->worldConnection);
        $this->assertDatabaseHas('base_items', ['id' => 2, 'specific_currency_price' => 125], $this->worldConnection);
    }

    public function test_it_sets_category_and_required_level_with_optional_prices(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'category',
            'value' => 'helmets',
            'prices' => [
                ['item_id' => 1, 'price' => 111],
                ['item_id' => 2, 'price' => 222],
            ],
        ])->assertRedirect();
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'required_level',
            'value' => 75,
            'prices' => [],
        ])->assertRedirect();

        foreach ([1, 2] as $itemId) {
            $this->assertDatabaseHas('base_items', ['id' => $itemId, 'category' => 'helmets'], $this->worldConnection);
            $this->assertSame(75, $this->baseItemAttributes($itemId)['needLevel']);
        }
        $this->assertDatabaseHas('base_items', ['id' => 1, 'price' => 111], $this->worldConnection);
        $this->assertDatabaseHas('base_items', ['id' => 2, 'price' => 222], $this->worldConnection);
    }

    public function test_it_sets_and_removes_a_legendary_bonus(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'legendary_bonus',
            'value' => 'pushBack',
        ])->assertRedirect();

        $this->assertSame(['pushBack', 8], $this->baseItemAttributes(1)['legendaryBon']);
        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'legendary_bonus',
            'value' => 'none',
        ])->assertRedirect();
        $this->assertArrayNotHasKey('legendaryBon', $this->baseItemAttributes(1));
        $this->assertSame(['pushBack', 8], $this->baseItemAttributes(2)['legendaryBon']);
    }

    public function test_it_sets_and_clears_item_lifespan_attributes(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1, 2],
            'operation' => 'lifespan',
            'attribute_key' => 'expiresOn',
            'value' => 1_800_000_000,
        ])->assertRedirect();
        $this->assertSame(1_800_000_000, $this->baseItemAttributes(1)['expiresOn']);

        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'lifespan',
            'attribute_key' => 'expiresOn',
            'value' => null,
        ])->assertRedirect();
        $this->assertArrayNotHasKey('expiresOn', $this->baseItemAttributes(1));
        $this->assertSame(1_800_000_000, $this->baseItemAttributes(2)['expiresOn']);
    }

    public function test_it_clears_selected_keys_from_each_attribute_column(): void
    {
        DB::connection($this->worldConnection)->table('base_items')->where('id', 1)->update([
            'attributes' => json_encode(['needLevel' => 10, 'description' => 'Zostaje']),
            'attribute_points' => json_encode(['strength' => 5, 'armor' => 3]),
            'manual_attribute_points' => json_encode(['counter' => 2]),
        ]);

        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'clear_attributes',
            'attribute_paths' => [
                'attributes:needLevel',
                'attribute_points:strength',
                'manual_attribute_points:counter',
            ],
        ])->assertRedirect();

        $this->assertSame(['description' => 'Zostaje'], $this->baseItemAttributes(1));
        $this->assertSame(
            ['armor' => 3],
            json_decode((string) DB::connection($this->worldConnection)->table('base_items')->where('id', 1)->value('attribute_points'), true)
        );
        $this->assertDatabaseHas('base_items', ['id' => 1, 'manual_attribute_points' => null], $this->worldConnection);
    }

    public function test_it_replaces_prefixes_and_suffixes_item_names(): void
    {
        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'name',
            'name_mode' => 'replace',
            'search_phrase' => 'Przedmiot',
            'replacement_phrase' => 'Hełm',
        ])->assertRedirect();
        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'name',
            'name_mode' => 'prefix',
            'replacement_phrase' => 'Stary ',
        ])->assertRedirect();
        $this->bulkUpdate([
            'item_ids' => [1],
            'operation' => 'name',
            'name_mode' => 'suffix',
            'replacement_phrase' => ' +1',
        ])->assertRedirect();

        $this->assertDatabaseHas('base_items', ['id' => 1, 'name' => 'Stary Hełm 1 +1'], $this->worldConnection);
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

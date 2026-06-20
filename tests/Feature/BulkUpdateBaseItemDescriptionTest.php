<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BulkUpdateBaseItemDescriptionTest extends TestCase
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

        $this->worldConnection = config('database.default');

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
    }

    public function test_it_updates_descriptions_for_selected_base_items(): void
    {
        $this->seedBaseItems();

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => $this->worldConnection])
            ->patch(route('base-items.bulk.description.update'), [
                'item_ids' => [1, 2, 4, 5],
                'search_phrase' => 'Wigilia 2025 r.',
                'replacement_phrase' => 'Wigilia #YEAR r.',
            ]);

        $response->assertRedirect();

        $this->assertSame('Wigilia #YEAR r. wraca do gry.', $this->baseItemDescription(1));
        $this->assertSame('Wigilia #YEAR r. i Wigilia #YEAR r.', $this->baseItemDescription(2));
        $this->assertSame('Wigilia 2025 r. poza zaznaczeniem.', $this->baseItemDescription(3));
        $this->assertSame('Opis bez poprawianej frazy.', $this->baseItemDescription(4));
        $this->assertNull($this->baseItemDescription(5));

        $this->assertDatabaseHas('base_items', [
            'id' => 1,
            'edited_manually' => true,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 2,
            'edited_manually' => true,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 3,
            'edited_manually' => false,
        ], $this->worldConnection);
        $this->assertDatabaseHas('base_items', [
            'id' => 4,
            'edited_manually' => false,
        ], $this->worldConnection);
    }

    public function test_it_requires_search_phrase(): void
    {
        $this->seedBaseItems();

        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => $this->worldConnection])
            ->patch(route('base-items.bulk.description.update'), [
                'item_ids' => [1],
                'search_phrase' => '',
                'replacement_phrase' => 'Wigilia #YEAR r.',
            ]);

        $response->assertSessionHasErrors('search_phrase');
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'base-item-bulk-editor',
            'name' => 'Base Item Bulk Editor',
            'email' => 'base-item-bulk-editor@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }

    private function seedBaseItems(): void
    {
        DB::connection($this->worldConnection)->table('base_items')->insert([
            $this->baseItemRecord(1, 'Wigilia #1', ['description' => 'Wigilia 2025 r. wraca do gry.']),
            $this->baseItemRecord(2, 'Wigilia #2', ['description' => 'Wigilia 2025 r. i Wigilia 2025 r.']),
            $this->baseItemRecord(3, 'Wigilia #3', ['description' => 'Wigilia 2025 r. poza zaznaczeniem.']),
            $this->baseItemRecord(4, 'Wigilia #4', ['description' => 'Opis bez poprawianej frazy.']),
            $this->baseItemRecord(5, 'Wigilia #5', null),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $attributes
     * @return array<string, mixed>
     */
    private function baseItemRecord(int $id, string $name, ?array $attributes): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'src' => "items/{$id}.gif",
            'stats' => '',
            'cl' => 0,
            'pr' => 0,
            'edited_manually' => false,
            'attributes' => $attributes === null ? null : json_encode($attributes),
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

    private function baseItemDescription(int $id): ?string
    {
        $attributes = DB::connection($this->worldConnection)
            ->table('base_items')
            ->where('id', $id)
            ->value('attributes');

        if (! is_string($attributes)) {
            return null;
        }

        return json_decode($attributes, true)['description'] ?? null;
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BaseItemIndexGlobalFilterTest extends TestCase
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

        Schema::connection('retro')->dropIfExists('base_item_usage_views');
        Schema::connection('retro')->dropIfExists('base_items');

        Schema::connection('retro')->create('base_items', function (Blueprint $table): void {
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

        Schema::connection('retro')->create('base_item_usage_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('base_item_id')->primary();
            $table->boolean('is_in_use')->default(false);
            $table->unsignedInteger('source_count')->default(0);
            $table->json('sources')->nullable();
            $table->timestamps();
        });

        DB::connection('retro')->table('base_items')->insert([
            'id' => 1,
            'name' => 'Test sword',
            'src' => 'items/test-sword.gif',
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
        ]);

        DB::connection('retro')->table('base_item_usage_views')->insert([
            'base_item_id' => 1,
            'is_in_use' => false,
            'source_count' => 0,
            'sources' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_returns_successfully_when_global_filter_is_used(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.index', [
                'tables' => [
                    'default' => [
                        'page' => 1,
                        'perPage' => 100,
                        'globalFilter' => 'test',
                        'filters' => [],
                        'sortOrder' => 'ASC',
                        'sortField' => 'id',
                    ],
                ],
            ]));

        $response->assertOk();
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'base-item-editor',
            'name' => 'Base Item Editor',
            'email' => 'base-item-editor@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}

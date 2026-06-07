<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BaseItemIndexAttributeFilterTest extends TestCase
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

        Schema::connection('retro')->disableForeignKeyConstraints();
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
            [
                'id' => 10,
                'name' => 'Anielski amulet',
                'src' => 'items/amulet.gif',
                'stats' => '',
                'cl' => 0,
                'pr' => 0,
                'edited_manually' => false,
                'attributes' => json_encode([
                    'description' => 'Przedmiot wzmacnia leczenie po walce.',
                    'legendaryBon' => ['angelTouchHealingChance', 5],
                    'combatFlee' => true,
                ]),
                'attribute_points' => null,
                'manual_attribute_points' => null,
                'reverse_attributes' => null,
                'rarity' => 'legendary',
                'category' => 'necklaces',
                'price' => null,
                'currency' => null,
                'specific_currency_price' => null,
                'deleted_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'name' => 'Ciężki topór',
                'src' => 'items/topor.gif',
                'stats' => '',
                'cl' => 0,
                'pr' => 0,
                'edited_manually' => false,
                'attributes' => json_encode([
                    'description' => 'Przedmiot zwiększa siłę ataku.',
                    'legendaryBon' => ['pushBack', 8],
                ]),
                'attribute_points' => null,
                'manual_attribute_points' => null,
                'reverse_attributes' => null,
                'rarity' => 'legendary',
                'category' => 'twoHanded',
                'price' => null,
                'currency' => null,
                'specific_currency_price' => null,
                'deleted_at' => null,
                'created_at' => now()->subSecond(),
                'updated_at' => now()->subSecond(),
            ],
            [
                'id' => 12,
                'name' => 'Zwinne rękawice',
                'src' => 'items/gloves.gif',
                'stats' => '',
                'cl' => 0,
                'pr' => 0,
                'edited_manually' => false,
                'attributes' => json_encode([
                    'description' => 'Przedmiot podnosi zręczność.',
                ]),
                'attribute_points' => json_encode([
                    'criticalChance' => 3,
                ]),
                'manual_attribute_points' => null,
                'reverse_attributes' => null,
                'rarity' => 'unique',
                'category' => 'gloves',
                'price' => null,
                'currency' => null,
                'specific_currency_price' => null,
                'deleted_at' => null,
                'created_at' => now()->subSeconds(2),
                'updated_at' => now()->subSeconds(2),
            ],
            [
                'id' => 13,
                'name' => 'Ciężka zbroja',
                'src' => 'items/armor.gif',
                'stats' => '',
                'cl' => 0,
                'pr' => 0,
                'edited_manually' => false,
                'attributes' => json_encode([
                    'description' => 'Przedmiot wzmacnia obronę.',
                ]),
                'attribute_points' => null,
                'manual_attribute_points' => json_encode([
                    'armor' => 2,
                ]),
                'reverse_attributes' => null,
                'rarity' => 'unique',
                'category' => 'armors',
                'price' => null,
                'currency' => null,
                'specific_currency_price' => null,
                'deleted_at' => null,
                'created_at' => now()->subSeconds(3),
                'updated_at' => now()->subSeconds(3),
            ],
        ]);

        DB::connection('retro')->table('base_item_usage_views')->insert([
            [
                'base_item_id' => 10,
                'is_in_use' => false,
                'source_count' => 0,
                'sources' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'base_item_id' => 11,
                'is_in_use' => false,
                'source_count' => 0,
                'sources' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'base_item_id' => 12,
                'is_in_use' => false,
                'source_count' => 0,
                'sources' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'base_item_id' => 13,
                'is_in_use' => false,
                'source_count' => 0,
                'sources' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::connection('retro')->enableForeignKeyConstraints();
    }

    public function test_it_filters_base_items_by_description_attribute(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.index', [
                'description' => 'leczenie',
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('BaseItem/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', 10)
            ->where('filters.description', 'leczenie'));
    }

    public function test_it_filters_base_items_by_legendary_bonus_attribute(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.index', [
                'legendary_bonus' => 'pushBack',
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('BaseItem/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', 11)
            ->where('filters.legendary_bonus', 'pushBack'));
    }

    public function test_it_filters_base_items_by_attribute_key(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.index', [
                'attribute_keys' => ['combatFlee'],
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('BaseItem/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', 10)
            ->where('filters.attribute_keys.0', 'combatFlee'));
    }

    public function test_it_filters_base_items_by_attribute_point_key(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.index', [
                'attribute_keys' => ['criticalChance'],
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('BaseItem/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', 12)
            ->where('filters.attribute_keys.0', 'criticalChance'));
    }

    public function test_it_filters_base_items_by_manual_attribute_point_key(): void
    {
        $response = $this
            ->actingAs($this->makeUser())
            ->withSession(['world' => 'retro'])
            ->get(route('base-items.index', [
                'attribute_keys' => ['armor'],
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('BaseItem/Index')
            ->has('items.data', 1)
            ->where('items.data.0.id', 13)
            ->where('filters.attribute_keys.0', 'armor'));
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => 'base-item-filter',
            'name' => 'Base Item Filter',
            'email' => 'base-item-filter@example.com',
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}

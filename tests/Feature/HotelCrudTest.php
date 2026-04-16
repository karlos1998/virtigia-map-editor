<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HotelCrudTest extends TestCase
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

        Schema::connection('retro')->dropIfExists('hotel_rooms');
        Schema::connection('retro')->dropIfExists('hotels');
        Schema::connection('retro')->dropIfExists('doors');
        Schema::connection('retro')->dropIfExists('maps');
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

        Schema::connection('retro')->create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src')->default('');
            $table->unsignedInteger('x')->default(1);
            $table->unsignedInteger('y')->default(1);
            $table->text('col')->nullable();
            $table->text('water')->nullable();
            $table->string('pvp')->nullable();
            $table->text('battleground')->nullable();
            $table->text('battleground2')->nullable();
            $table->boolean('is_teleport_locked')->default(false);
            $table->timestamps();
        });

        Schema::connection('retro')->create('doors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('map_id')->constrained('maps');
            $table->integer('x');
            $table->integer('y');
            $table->foreignId('go_map_id')->constrained('maps');
            $table->integer('go_x');
            $table->integer('go_y');
            $table->integer('min_lvl')->nullable();
            $table->integer('max_lvl')->nullable();
            $table->foreignId('required_base_item_id')->nullable()->constrained('base_items');
            $table->timestamps();
        });

        Schema::connection('retro')->create('hotels', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('currency')->default('dragonTear');
            $table->string('period')->default('month');
            $table->timestamps();
        });

        Schema::connection('retro')->create('hotel_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hotel_id')->constrained('hotels')->cascadeOnDelete();
            $table->foreignId('base_item_id')->constrained('base_items');
            $table->foreignId('door_id')->constrained('doors');
            $table->integer('price')->default(0);
            $table->timestamps();
            $table->unique(['hotel_id', 'door_id']);
        });

        DB::connection('retro')->table('base_items')->insert([
            'id' => 1,
            'name' => 'Klucz do pokoju 1',
            'src' => 'items/key-1.gif',
            'stats' => '',
            'cl' => 0,
            'pr' => 0,
            'edited_manually' => false,
            'attributes' => null,
            'attribute_points' => null,
            'manual_attribute_points' => null,
            'reverse_attributes' => null,
            'rarity' => null,
            'category' => 'keys',
            'price' => null,
            'currency' => null,
            'specific_currency_price' => null,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection('retro')->table('maps')->insert([
            [
                'id' => 1,
                'name' => 'Karczma',
                'src' => 'maps/karczma.png',
                'x' => 10,
                'y' => 10,
                'col' => null,
                'water' => null,
                'pvp' => null,
                'battleground' => null,
                'battleground2' => null,
                'is_teleport_locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Pokój nr 1',
                'src' => 'maps/pokoj-1.png',
                'x' => 10,
                'y' => 10,
                'col' => null,
                'water' => null,
                'pvp' => null,
                'battleground' => null,
                'battleground2' => null,
                'is_teleport_locked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::connection('retro')->table('doors')->insert([
            'id' => 1,
            'map_id' => 1,
            'x' => 5,
            'y' => 5,
            'go_map_id' => 2,
            'go_x' => 1,
            'go_y' => 1,
            'min_lvl' => null,
            'max_lvl' => null,
            'required_base_item_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_can_create_a_hotel_and_add_a_room(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->withSession(['world' => 'retro'])
            ->post(route('hotels.store'), [
                'name' => 'Zajazd u Makiny',
                'currency' => 'dragonTear',
                'period' => 'month',
            ])
            ->assertRedirect();

        $hotelId = DB::connection('retro')->table('hotels')->value('id');

        $this->actingAs($user)
            ->withSession(['world' => 'retro'])
            ->post(route('hotels.rooms.store', ['hotel' => $hotelId]), [
                'base_item_id' => 1,
                'price' => 250,
                'door_id' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('hotels', [
            'id' => $hotelId,
            'name' => 'Zajazd u Makiny',
            'currency' => 'dragonTear',
            'period' => 'month',
        ], 'retro');

        $this->assertDatabaseHas('hotel_rooms', [
            'hotel_id' => $hotelId,
            'base_item_id' => 1,
            'price' => 250,
            'door_id' => 1,
        ], 'retro');
    }

    private function makeUser(): User
    {
        return User::query()->create([
            'login' => fake()->unique()->userName(),
            'name' => 'Hotel Editor',
            'email' => fake()->unique()->safeEmail(),
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => ['admin'],
            'permissions' => ['world.write'],
        ]);
    }
}

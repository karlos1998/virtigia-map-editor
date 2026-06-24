<?php

namespace Tests\Feature;

use App\Models\DynamicModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Tests\TestCase;

class ExportTitanSpecialAttacksCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSqliteWorld('retro', 'testing-titan-special-attacks.sqlite');
        $this->createWorldSchema('retro');
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    /**
     * @throws JsonException
     */
    public function test_it_exports_titan_special_attacks_with_assigned_mob_levels(): void
    {
        $this->seedWorld();

        $exitCode = Artisan::call('titans:special-attacks', [
            'world' => 'retro',
        ]);

        $payload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertSame('retro', $payload['world']);
        $this->assertSame('retro', $payload['connection']);
        $this->assertSame('TITAN', $payload['filters']['rank']['value']);
        $this->assertSame(2, $payload['summary']['titan_count']);
        $this->assertSame(1, $payload['summary']['special_attack_count']);
        $this->assertSame(2, $payload['summary']['assignment_count']);

        $attack = $payload['attacks'][0];

        $this->assertSame('Płomień tytana', $attack['name']);
        $this->assertSame('special', $attack['attack_type']['value']);
        $this->assertSame('all', $attack['target']['value']);
        $this->assertFalse($attack['random_target']);
        $this->assertSame('fire', $attack['damages'][0]['element']['value']);
        $this->assertSame(100, $attack['damages'][0]['min_damage']);
        $this->assertSame(300, $attack['damages'][0]['max_damage']);
        $this->assertSame('stun', $attack['effects'][0]['type']['value']);
        $this->assertSame(2, $attack['assigned_titans_count']);

        $assignedTitanNames = array_column($attack['assigned_titans'], 'name');

        $this->assertSame(['Niższy tytan', 'Wyższy tytan'], $assignedTitanNames);
        $this->assertSame(120, $attack['assigned_titans'][0]['level']);
        $this->assertSame('Smoki', $attack['assigned_titans'][0]['mob_species'][0]['name']);
        $this->assertSame('2026-06-01T10:00:00+00:00', $attack['assigned_titans'][0]['assignment']['created_at']);
    }

    private function configureSqliteWorld(string $connection, string $databaseFile): void
    {
        $databasePath = database_path($databaseFile);

        config()->set("database.connections.{$connection}", [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        if (! file_exists($databasePath)) {
            touch($databasePath);
        }
    }

    private function createWorldSchema(string $connection): void
    {
        $schema = Schema::connection($connection);

        $schema->disableForeignKeyConstraints();

        foreach ([
            'base_npc_mob_species',
            'mob_species',
            'base_npc_special_attacks',
            'special_attack_damages',
            'special_attack_effects',
            'special_attacks',
            'base_npcs',
        ] as $table) {
            $schema->dropIfExists($table);
        }

        $schema->enableForeignKeyConstraints();

        $schema->create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name')->nullable();
            $table->string('src')->default('');
            $table->integer('lvl')->default(0);
            $table->integer('type')->default(0);
            $table->integer('wt')->default(0);
            $table->string('rank')->default('NORMAL');
            $table->string('category')->default('MOB');
            $table->string('profession')->default('w');
        });

        $schema->create('special_attacks', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('attack_type');
            $table->integer('charge_turns')->default(0);
            $table->string('target');
            $table->boolean('random_target')->default(false);
        });

        $schema->create('special_attack_damages', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('special_attack_id');
            $table->string('element');
            $table->integer('min_damage');
            $table->integer('max_damage');
        });

        $schema->create('special_attack_effects', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->foreignId('special_attack_id');
            $table->string('type');
            $table->float('value');
            $table->integer('duration');
        });

        $schema->create('base_npc_special_attacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_npc_id');
            $table->foreignId('special_attack_id');
            $table->timestamps();
        });

        $schema->create('mob_species', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $schema->create('base_npc_mob_species', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_npc_id');
            $table->foreignId('mob_species_id');
            $table->timestamps();
        });
    }

    private function seedWorld(): void
    {
        $now = '2026-06-01 10:00:00';

        DB::connection('retro')->table('base_npcs')->insert([
            [
                'id' => 1,
                'name' => 'Niższy tytan',
                'src' => 'retro/titan-low.gif',
                'lvl' => 120,
                'type' => 1,
                'wt' => 10,
                'rank' => 'TITAN',
                'category' => 'MOB',
                'profession' => 'w',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Wyższy tytan',
                'src' => 'retro/titan-high.gif',
                'lvl' => 150,
                'type' => 2,
                'wt' => 20,
                'rank' => 'TITAN',
                'category' => 'MOB',
                'profession' => 'm',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Heros kontrolny',
                'src' => 'retro/hero.gif',
                'lvl' => 130,
                'type' => 3,
                'wt' => 30,
                'rank' => 'HERO',
                'category' => 'MOB',
                'profession' => 'p',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::connection('retro')->table('special_attacks')->insert([
            [
                'id' => 1,
                'name' => 'Płomień tytana',
                'attack_type' => 'special',
                'charge_turns' => 2,
                'target' => 'all',
                'random_target' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Cios herosa',
                'attack_type' => 'special',
                'charge_turns' => 1,
                'target' => 'single',
                'random_target' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::connection('retro')->table('special_attack_damages')->insert([
            'id' => 1,
            'special_attack_id' => 1,
            'element' => 'fire',
            'min_damage' => 100,
            'max_damage' => 300,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('special_attack_effects')->insert([
            'id' => 1,
            'special_attack_id' => 1,
            'type' => 'stun',
            'value' => 1,
            'duration' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('base_npc_special_attacks')->insert([
            [
                'base_npc_id' => 1,
                'special_attack_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'base_npc_id' => 2,
                'special_attack_id' => 1,
                'created_at' => '2026-06-01 11:00:00',
                'updated_at' => '2026-06-01 11:00:00',
            ],
            [
                'base_npc_id' => 3,
                'special_attack_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::connection('retro')->table('mob_species')->insert([
            'id' => 1,
            'name' => 'Smoki',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('base_npc_mob_species')->insert([
            'base_npc_id' => 1,
            'mob_species_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

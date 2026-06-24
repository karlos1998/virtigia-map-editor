<?php

namespace Tests\Feature;

use App\Models\DynamicModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Tests\TestCase;

class RebalanceTitanSpecialAttacksCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSqliteWorld('retro', 'testing-titan-special-attack-rebalance.sqlite');
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
    public function test_it_reports_percentage_changes_without_applying_them(): void
    {
        $this->seedMaddok();
        $file = $this->writePayloadFile([
            'id' => 3328,
            'name' => 'Maddok Magua',
            'special_attacks' => [
                [
                    'id' => 269,
                    'name' => 'Gromowładne uderzenie włócznią',
                    'charge_turns' => 4,
                    'target' => 'all',
                    'damages' => [
                        [
                            'element' => 'physical',
                            'min_damage' => 50,
                            'max_damage' => 150,
                        ],
                    ],
                ],
            ],
        ]);

        $exitCode = Artisan::call('titans:rebalance-special-attacks', [
            'file' => $file,
            '--world' => 'retro',
        ]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Gromowładne uderzenie włócznią', $output);
        $this->assertStringContainsString('50.00%', $output);
        $this->assertStringContainsString('Dry-run', $output);
        $this->assertSame(100, DB::connection('retro')->table('special_attack_damages')->where('id', 1)->value('min_damage'));
        $this->assertSame(3, DB::connection('retro')->table('special_attacks')->where('id', 269)->value('charge_turns'));
    }

    /**
     * @throws JsonException
     */
    public function test_it_applies_target_values_when_apply_flag_is_used(): void
    {
        $this->seedMaddok();
        $file = $this->writePayloadFile([
            'id' => 3328,
            'name' => 'Maddok Magua',
            'special_attacks' => [
                [
                    'id' => 269,
                    'damages' => [
                        [
                            'element' => 'physical',
                            'min_damage' => 50,
                            'max_damage' => 150,
                        ],
                    ],
                    'effects' => [
                        [
                            'type' => 'addSA',
                            'value' => 5,
                            'duration' => 4,
                        ],
                    ],
                ],
            ],
        ]);

        $exitCode = Artisan::call('titans:rebalance-special-attacks', [
            'file' => $file,
            '--world' => 'retro',
            '--apply' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertSame(50, DB::connection('retro')->table('special_attack_damages')->where('id', 1)->value('min_damage'));
        $this->assertSame(150, DB::connection('retro')->table('special_attack_damages')->where('id', 1)->value('max_damage'));
        $this->assertSame(5.0, (float) DB::connection('retro')->table('special_attack_effects')->where('id', 1)->value('value'));
        $this->assertSame(4, DB::connection('retro')->table('special_attack_effects')->where('id', 1)->value('duration'));
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
            $table->integer('duration')->nullable();
        });

        $schema->create('base_npc_special_attacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('base_npc_id');
            $table->foreignId('special_attack_id');
            $table->timestamps();
        });
    }

    private function seedMaddok(): void
    {
        $now = '2026-06-01 10:00:00';

        DB::connection('retro')->table('base_npcs')->insert([
            'id' => 3328,
            'name' => 'Maddok Magua',
            'src' => 'maddok.gif',
            'lvl' => 231,
            'type' => 1,
            'wt' => 10,
            'rank' => 'TITAN',
            'category' => 'MOB',
            'profession' => 'h',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('special_attacks')->insert([
            'id' => 269,
            'name' => 'Gromowładne uderzenie włócznią',
            'attack_type' => 'special',
            'charge_turns' => 3,
            'target' => 'all',
            'random_target' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('special_attack_damages')->insert([
            'id' => 1,
            'special_attack_id' => 269,
            'element' => 'physical',
            'min_damage' => 100,
            'max_damage' => 300,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('special_attack_effects')->insert([
            'id' => 1,
            'special_attack_id' => 269,
            'type' => 'addSA',
            'value' => 10,
            'duration' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('retro')->table('base_npc_special_attacks')->insert([
            'base_npc_id' => 3328,
            'special_attack_id' => 269,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    private function writePayloadFile(array $payload): string
    {
        $path = tempnam(sys_get_temp_dir(), 'maddok-special-attacks-');
        $this->assertIsString($path);

        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}

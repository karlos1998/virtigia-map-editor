<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RemoteBaseNpcBackfillMigrationTest extends TestCase
{
    private string $originalDefaultConnection;

    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = config('database.default');
        $this->databasePath = database_path('testing-remote-base-npc-backfill.sqlite');

        File::delete($this->databasePath);
        File::put($this->databasePath, '');

        config()->set('database.connections.remote_backfill', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        config()->set('database.default', 'remote_backfill');
        DB::purge('remote_backfill');
    }

    protected function tearDown(): void
    {
        config()->set('database.default', $this->originalDefaultConnection);
        DB::purge('remote_backfill');
        File::delete($this->databasePath);

        parent::tearDown();
    }

    public function test_base_npc_backfill_supports_fresh_remote_migration_order(): void
    {
        $this->runRemoteMigration('0000_00_01_000005_create_base_npcs_table.php');
        $this->runRemoteMigration('2023_11_30_000000_add_is_aggressive_to_base_npcs_table.php');
        $this->runRemoteMigration('2024_12_28_164551_create_base_npcs_table.php');
        $this->runRemoteMigration('2025_01_17_145731_add_profession_to_base_npcs_table.php');

        $columns = Schema::getColumnListing('base_npcs');

        $this->assertContains('profession', $columns);
        $this->assertContains('is_aggressive', $columns);
        $this->assertSame(1, collect($columns)->filter(fn (string $column): bool => $column === 'profession')->count());
    }

    public function test_base_npc_backfill_skips_existing_remote_table(): void
    {
        Schema::create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->string('profession')->default('w');
            $table->boolean('is_aggressive')->default(true);
            $table->timestamps();
        });

        $this->runRemoteMigration('0000_00_01_000005_create_base_npcs_table.php');
        $this->runRemoteMigration('2023_11_30_000000_add_is_aggressive_to_base_npcs_table.php');
        $this->runRemoteMigration('2024_12_28_164551_create_base_npcs_table.php');
        $this->runRemoteMigration('2025_01_17_145731_add_profession_to_base_npcs_table.php');

        $columns = Schema::getColumnListing('base_npcs');

        $this->assertSame(1, collect($columns)->filter(fn (string $column): bool => $column === 'profession')->count());
        $this->assertSame(1, collect($columns)->filter(fn (string $column): bool => $column === 'is_aggressive')->count());
    }

    private function runRemoteMigration(string $filename): void
    {
        $migration = require database_path("migrations/remote/{$filename}");

        $migration->up();
    }
}

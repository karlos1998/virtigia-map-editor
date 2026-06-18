<?php

namespace Tests\Feature;

use App\Models\DynamicModel;
use App\Services\BaseItemUsageViewService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DynamicModelConnectionIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSqliteWorld('retro', 'testing-connection-retro.sqlite');
        $this->configureSqliteWorld('legacy', 'testing-connection-legacy.sqlite');

        $this->createWorldSchema('retro');
        $this->createWorldSchema('legacy');
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();

        parent::tearDown();
    }

    public function test_explicit_connection_overrides_global_connection_in_usage_refresh(): void
    {
        $this->seedItem('retro', false);
        $this->seedItem('legacy', true);

        DynamicModel::setGlobalConnection('retro');

        app(BaseItemUsageViewService::class)->refreshChunk('legacy', 0, 500);

        $this->assertFalse(
            (bool) DB::connection('retro')->table('base_item_usage_views')->where('base_item_id', 1)->value('is_in_use')
        );
        $this->assertTrue(
            (bool) DB::connection('legacy')->table('base_item_usage_views')->where('base_item_id', 1)->value('is_in_use')
        );
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

        foreach ([
            'base_item_usage_views',
            'base_npc_loots',
            'npc_locations',
            'npcs',
            'dialog_node_options',
            'dialog_nodes',
            'dialogs',
            'shop_items',
            'shops',
            'maps',
            'base_npcs',
            'base_items',
        ] as $table) {
            $schema->dropIfExists($table);
        }

        $schema->create('base_items', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('src');
            $table->text('stats');
            $table->integer('cl')->default(0);
            $table->integer('pr')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('base_npcs', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('src');
            $table->integer('lvl')->default(0);
            $table->integer('type')->default(0);
            $table->integer('wt')->default(0);
            $table->timestamps();
        });

        $schema->create('base_npc_loots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('base_npc_id');
            $table->unsignedBigInteger('base_item_id');
            $table->timestamps();
        });

        $schema->create('base_item_usage_views', function (Blueprint $table): void {
            $table->unsignedBigInteger('base_item_id')->primary();
            $table->boolean('is_in_use')->default(false);
            $table->unsignedInteger('source_count')->default(0);
            $table->json('sources')->nullable();
            $table->timestamps();
        });

        $schema->create('shops', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });

        $schema->create('shop_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('item_id');
        });

        $schema->create('dialogs', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });

        $schema->create('dialog_nodes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_dialog_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->json('additional_actions')->nullable();
        });

        $schema->create('dialog_node_options', function (Blueprint $table): void {
            $table->id();
            $table->json('rules')->nullable();
        });

        $schema->create('npcs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('base_npc_id')->nullable();
            $table->unsignedBigInteger('dialog_id')->nullable();
            $table->boolean('enabled')->default(true);
        });

        $schema->create('npc_locations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('npc_id')->nullable();
            $table->unsignedBigInteger('map_id')->nullable();
            $table->integer('x')->nullable();
            $table->integer('y')->nullable();
        });

        $schema->create('maps', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });
    }

    private function seedItem(string $connection, bool $attachLoot): void
    {
        DB::connection($connection)->table('base_items')->insert([
            'id' => 1,
            'name' => "Item {$connection}",
            'src' => 'item.gif',
            'stats' => '',
            'cl' => 0,
            'pr' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection($connection)->table('base_item_usage_views')->insert([
            'base_item_id' => 1,
            'is_in_use' => false,
            'source_count' => 0,
            'sources' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $attachLoot) {
            return;
        }

        DB::connection($connection)->table('base_npcs')->insert([
            'id' => 1,
            'name' => "Npc {$connection}",
            'src' => 'npc.gif',
            'lvl' => 1,
            'type' => 0,
            'wt' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::connection($connection)->table('base_npc_loots')->insert([
            'base_npc_id' => 1,
            'base_item_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

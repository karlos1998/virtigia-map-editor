<?php

namespace Tests\Feature;

use App\Models\Dialog;
use App\Models\DynamicModel;
use App\Services\DialogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DialogDeletionTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = database_path('testing-dialog-deletion.sqlite');

        if (! file_exists($this->databasePath)) {
            touch($this->databasePath);
        }

        config()->set('database.connections.dialog_deletion', [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('dialog_deletion');
        DynamicModel::setGlobalConnection('dialog_deletion');

        $this->createTables();
    }

    protected function tearDown(): void
    {
        DynamicModel::clearGlobalConnection();
        DB::disconnect('dialog_deletion');

        if (file_exists($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_it_deletes_the_dialog_graph_and_detaches_npcs_without_deleting_them(): void
    {
        DB::connection('dialog_deletion')->table('dialogs')->insert([
            'id' => 10,
            'name' => 'Dialog do usunięcia',
        ]);
        DB::connection('dialog_deletion')->table('dialog_nodes')->insert([
            ['id' => 20, 'source_dialog_id' => 10, 'type' => 'start', 'position' => '{}'],
            ['id' => 21, 'source_dialog_id' => 10, 'type' => 'special', 'position' => '{}'],
        ]);
        DB::connection('dialog_deletion')->table('dialog_node_options')->insert([
            'id' => 30,
            'node_id' => 21,
            'label' => 'Koniec',
        ]);
        DB::connection('dialog_deletion')->table('dialog_edges')->insert([
            'id' => 40,
            'source_dialog_id' => 10,
            'source_node_id' => 20,
            'source_option_id' => null,
            'target_node_id' => 21,
        ]);
        DB::connection('dialog_deletion')->table('npcs')->insert([
            'id' => 50,
            'base_npc_id' => 99,
            'dialog_id' => 10,
            'auto_start_dialog' => true,
        ]);

        $dialog = Dialog::query()->findOrFail(10);

        Dialog::withoutEvents(fn () => app(DialogService::class)->destroy($dialog));

        $this->assertDatabaseMissing('dialogs', ['id' => 10], 'dialog_deletion');
        $this->assertDatabaseCount('dialog_nodes', 0, 'dialog_deletion');
        $this->assertDatabaseCount('dialog_node_options', 0, 'dialog_deletion');
        $this->assertDatabaseCount('dialog_edges', 0, 'dialog_deletion');
        $this->assertDatabaseHas('npcs', [
            'id' => 50,
            'base_npc_id' => 99,
            'dialog_id' => null,
            'auto_start_dialog' => false,
        ], 'dialog_deletion');
    }

    private function createTables(): void
    {
        Schema::connection('dialog_deletion')->create('dialogs', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('dialog_deletion')->create('dialog_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_dialog_id')->constrained('dialogs');
            $table->string('type');
            $table->json('position');
            $table->timestamps();
        });

        Schema::connection('dialog_deletion')->create('dialog_node_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('node_id')->constrained('dialog_nodes');
            $table->string('label');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::connection('dialog_deletion')->create('dialog_edges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_dialog_id')->constrained('dialogs');
            $table->foreignId('source_node_id')->nullable()->constrained('dialog_nodes');
            $table->foreignId('source_option_id')->nullable()->constrained('dialog_node_options');
            $table->foreignId('target_node_id')->constrained('dialog_nodes');
            $table->timestamps();
        });

        Schema::connection('dialog_deletion')->create('npcs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('base_npc_id');
            $table->foreignId('dialog_id')->nullable()->constrained('dialogs');
            $table->boolean('auto_start_dialog')->default(false);
            $table->timestamps();
        });
    }
}

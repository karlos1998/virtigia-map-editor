<?php

namespace Tests\Feature;

use App\Jobs\ProcessWorldTemplateMigrations;
use App\Jobs\ProcessWorldTemplateUpdate;
use App\Models\User;
use App\Models\WorldTemplate;
use App\Models\WorldTemplateUpdate;
use App\Services\Mcp\McpWorldService;
use App\Services\WorldTemplateConnectionResolver;
use App\Services\WorldTemplateMigrationService;
use App\Services\WorldTemplateSnapshotService;
use App\Services\WorldTemplateUpdateService;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class WorldTemplateUpdateTest extends TestCase
{
    use RefreshDatabase;

    private Connection $source;

    private Connection $target;

    private WorldTemplate $production;

    private WorldTemplate $base;

    private array $paths = [];

    private array $mysqlDatabases = [];

    private array $authoringTables = [];

    private array $migrationDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->authoringTables = config('world_templates.update_tables');
        config()->set('world_templates.update_tables', ['items', 'nodes', 'links']);

        $passwordFile = getenv('WORLD_TEMPLATE_TEST_MYSQL_PASSWORD_FILE');
        if ($passwordFile !== false) {
            config()->set('database.connections.update_test_admin', [
                'driver' => 'mysql', 'host' => 'mysql', 'port' => 3306,
                'username' => 'root', 'password' => File::get($passwordFile),
                'database' => null, 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci',
            ]);
        }

        foreach (['update_source', 'update_target'] as $name) {
            if ($passwordFile !== false) {
                $database = 'testing_template_'.$name.'_'.getmypid();
                DB::connection('update_test_admin')->getSchemaBuilder()->createDatabase($database);
                $this->mysqlDatabases[] = $database;
                config()->set('database.connections.'.$name, [...config('database.connections.update_test_admin'), 'database' => $database]);
            } else {
                $path = database_path($name.'_'.getmypid().'.sqlite');
                File::put($path, '');
                $this->paths[] = $path;
                config()->set('database.connections.'.$name, ['driver' => 'sqlite', 'database' => $path, 'foreign_key_constraints' => true]);
            }
            DB::purge($name);
            $connection = DB::connection($name);
            $connection->getSchemaBuilder()->create('items', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
            });
            $connection->getSchemaBuilder()->create('nodes', function (Blueprint $table): void {
                $table->id();
                $table->string('text');
                $table->foreignId('item_id')->nullable()->constrained('items');
            });
            $connection->getSchemaBuilder()->create('links', function (Blueprint $table): void {
                $table->foreignId('node_id')->constrained('nodes');
                $table->unsignedInteger('position');
                $table->string('text');
                $table->primary(['node_id', 'position']);
            });
        }
        $this->source = DB::connection('update_source');
        $this->target = DB::connection('update_target');
        $this->base = $this->makeTemplate('update_source', false);
        $this->production = $this->makeTemplate('update_target', true, $this->base->id);
        $this->source->table('items')->insert([['id' => 1, 'name' => 'Nowy miecz'], ['id' => 2, 'name' => 'Nowy przedmiot']]);
        $this->target->table('items')->insert([['id' => 1, 'name' => 'Stary miecz'], ['id' => 3, 'name' => 'Usunięty przedmiot']]);
        $this->source->table('nodes')->insert(['id' => 5, 'text' => 'Nowy dialog', 'item_id' => 2]);
        $this->source->table('links')->insert(['node_id' => 5, 'position' => 1, 'text' => 'Nowa opcja']);
    }

    protected function tearDown(): void
    {
        foreach (['update_source', 'update_target'] as $name) {
            DB::purge($name);
        }
        foreach ($this->paths as $path) {
            File::delete($path);
        }
        foreach ($this->migrationDirectories as $directory) {
            File::deleteDirectory($directory);
        }
        foreach (array_reverse($this->mysqlDatabases) as $database) {
            DB::connection('update_test_admin')->getSchemaBuilder()->dropDatabaseIfExists($database);
        }
        DB::purge('update_test_admin');
        parent::tearDown();
    }

    private function makeTemplate(string $name, bool $production, ?int $baseId = null): WorldTemplate
    {
        return WorldTemplate::query()->create([
            'name' => $name, 'slug' => $name, 'connection_name' => $name,
            'remote_database_server' => 'main1', 'database_name' => $name,
            'is_active' => true, 'is_visible' => true, 'is_production' => $production,
            'base_template_id' => $baseId,
        ]);
    }

    private function preview(): WorldTemplateUpdate
    {
        $update = app(WorldTemplateUpdateService::class)->start($this->production, 1);
        (new ProcessWorldTemplateUpdate($update->id))->handle(app(WorldTemplateSnapshotService::class));

        return $update->fresh();
    }

    private function apply(WorldTemplateUpdate $update): void
    {
        app(WorldTemplateUpdateService::class)->approve($update);
        (new ProcessWorldTemplateUpdate($update->id, true))->handle(app(WorldTemplateSnapshotService::class));
    }

    public function test_preview_is_persisted_and_production_is_unchanged_until_approval(): void
    {
        $update = $this->preview();
        $this->assertSame('ready', $update->status);
        $this->assertSame(['added' => 1, 'updated' => 1, 'deleted' => 1, 'unchanged' => 0], $update->summary['items']);
        $this->assertSame('Stary miecz', $this->target->table('items')->find(1)->name);
        $this->assertSame(5, $update->snapshotRows()->count());
        Queue::assertPushed(ProcessWorldTemplateUpdate::class);
    }

    public function test_approval_copies_the_reviewed_snapshot_preserving_ids_and_unrelated_game_data(): void
    {
        $this->target->getSchemaBuilder()->create('characters', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        $this->target->table('characters')->insert(['id' => 9, 'name' => 'Gracz']);
        $update = $this->preview();
        $this->source->table('items')->where('id', 1)->update(['name' => 'Późniejsza zmiana']);
        $this->apply($update);
        $this->assertSame('completed', $update->fresh()->status);
        $this->assertSame('Nowy miecz', $this->target->table('items')->find(1)->name);
        $this->assertNull($this->target->table('items')->find(3));
        $this->assertSame(2, $this->target->table('nodes')->find(5)->item_id);
        $this->assertSame('Nowa opcja', $this->target->table('links')->first()->text);
        $this->assertSame('Gracz', $this->target->table('characters')->find(9)->name);
    }

    public function test_it_rejects_stale_production_without_overwriting_it(): void
    {
        $update = $this->preview();
        $this->target->table('items')->where('id', 1)->update(['name' => 'Zmiana na produkcji']);
        try {
            $this->apply($update);
            $this->fail('Expected stale preview rejection.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Produkcja zmieniła', $error->getMessage());
        }
        $this->assertSame('Zmiana na produkcji', $this->target->table('items')->find(1)->name);
        $this->assertNotNull($this->target->table('items')->find(3));
    }

    public function test_an_invalid_reference_rolls_back_all_previously_copied_tables(): void
    {
        $this->source->getSchemaBuilder()->disableForeignKeyConstraints();
        $this->source->table('nodes')->where('id', 5)->update(['item_id' => 999]);
        $this->source->getSchemaBuilder()->enableForeignKeyConstraints();
        $update = $this->preview();
        try {
            $this->apply($update);
            $this->fail('Expected foreign key rejection.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('narusza powiązania', $error->getMessage());
        }
        $this->assertSame('Stary miecz', $this->target->table('items')->find(1)->name);
        $this->assertNotNull($this->target->table('items')->find(3));
        $this->assertSame(0, $this->target->table('nodes')->count());
        $foreignKeys = $this->target->getDriverName() === 'mysql'
            ? $this->target->selectOne('SELECT @@FOREIGN_KEY_CHECKS AS enabled')->enabled
            : $this->target->selectOne('PRAGMA foreign_keys')->foreign_keys;
        $this->assertSame(1, (int) $foreignKeys);
    }

    public function test_it_preserves_references_from_runtime_tables_by_rejecting_breaking_deletions(): void
    {
        $this->target->getSchemaBuilder()->create('character_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained('items');
        });
        $this->target->table('character_items')->insert(['item_id' => 3]);
        try {
            $this->apply($this->preview());
            $this->fail('Expected runtime reference rejection.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('character_items', $error->getMessage());
        }
        $this->assertNotNull($this->target->table('items')->find(3));
        $this->assertSame(1, $this->target->table('character_items')->count());
    }

    public function test_duplicate_starts_share_a_job_and_duplicate_approval_is_rejected(): void
    {
        $service = app(WorldTemplateUpdateService::class);
        $first = $service->start($this->production, 1);
        $second = $service->start($this->production, 2);
        $this->assertSame($first->id, $second->id);
        Queue::assertPushed(ProcessWorldTemplateUpdate::class, 1);
        (new ProcessWorldTemplateUpdate($first->id))->handle(app(WorldTemplateSnapshotService::class));
        $service->approve($first->fresh());
        $this->expectException(ValidationException::class);
        $service->approve($first->fresh());
    }

    public function test_expired_preview_cannot_be_applied(): void
    {
        $update = $this->preview();
        $update->update(['expires_at' => now()->subSecond()]);
        $this->expectException(ValidationException::class);
        app(WorldTemplateUpdateService::class)->approve($update);
    }

    public function test_production_is_not_selectable_or_resolvable_through_editor_or_mcp(): void
    {
        $resolver = app(WorldTemplateConnectionResolver::class);
        $this->assertNotContains($this->production->slug, $resolver->visibleSlugs());
        $this->assertNotContains($this->production->slug, $resolver->activeSlugs());
        $this->assertNull($resolver->resolve($this->production->slug));
        $this->actingAs(User::factory()->create())->post(route('switch-world'), ['world' => $this->production->slug])->assertSessionHasErrors('world');
        $this->actingAs(User::factory()->create())->withSession(['world' => $this->production->slug])->get(route('maps.index'))->assertRedirect(route('login'));
        $this->expectException(ValidationException::class);
        app(McpWorldService::class)->use($this->production->slug);
    }

    public function test_administrators_get_paginated_lightweight_diff_and_details_are_scoped(): void
    {
        $update = $this->preview();
        $admin = User::factory()->create(['roles' => ['administrator']]);
        $response = $this->actingAs($admin)->getJson(route('administration.world-template-updates.changes', ['update' => $update->id, 'table' => 'items', 'type' => 'updated']));
        $response->assertOk()->assertJsonPath('total', 1)->assertJsonMissingPath('data.0.after_data');
        $rowId = $response->json('data.0.id');
        $this->getJson(route('administration.world-template-updates.detail', ['update' => $update->id, 'row' => $rowId]))->assertOk()->assertJsonPath('after.name', 'Nowy miecz');
        $another = $this->preview();
        $this->getJson(route('administration.world-template-updates.detail', ['update' => $another->id, 'row' => $rowId]))->assertNotFound();
        $this->getJson(route('administration.world-template-updates.changes', ['update' => $update->id, 'table' => 'characters']))->assertUnprocessable();
    }

    public function test_non_administrators_cannot_start_inspect_or_apply_updates(): void
    {
        $update = $this->preview();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('administration.world-template-updates.store', $this->production->id), ['confirmed' => true])->assertForbidden();
        $this->getJson(route('administration.world-template-updates.show', $update->id))->assertForbidden();
        $this->getJson(route('administration.world-template-updates.changes', $update->id))->assertForbidden();
        $this->postJson(route('administration.world-template-updates.approve', $update->id), ['confirmed' => true])->assertForbidden();
    }

    public function test_update_endpoints_require_explicit_confirmation(): void
    {
        $update = $this->preview();
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]));
        $this->postJson(route('administration.world-template-updates.store', $this->production->id))->assertUnprocessable();
        $this->postJson(route('administration.world-template-updates.approve', $update->id))->assertUnprocessable();
    }

    public function test_schema_changes_are_rejected_before_production_is_touched(): void
    {
        $update = $this->preview();
        $this->target->getSchemaBuilder()->table('items', fn (Blueprint $table) => $table->string('extra')->nullable());
        try {
            $this->apply($update);
            $this->fail('Expected schema rejection.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('schemat', $error->getMessage());
        }
        $this->assertSame('Stary miecz', $this->target->table('items')->find(1)->name);
    }

    public function test_snapshot_corruption_rolls_back_the_target(): void
    {
        $update = $this->preview();
        $update->snapshotRows()->where('table_name', 'items')->where('change_type', 'updated')->update(['after_data' => json_encode(['id' => 1, 'name' => 'Niepoprawna migawka'])]);
        try {
            $this->apply($update);
            $this->fail('Expected snapshot verification rejection.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Weryfikacja kopii', $error->getMessage());
        }
        $this->assertSame('Stary miecz', $this->target->table('items')->find(1)->name);
        $this->assertNotNull($this->target->table('items')->find(3));
    }

    public function test_it_copies_cyclic_foreign_keys_in_one_transaction(): void
    {
        foreach ([$this->source, $this->target] as $connection) {
            $connection->getSchemaBuilder()->disableForeignKeyConstraints();
            $connection->getSchemaBuilder()->create('maps', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('spawn_id')->nullable()->constrained('spawns');
            });
            $connection->getSchemaBuilder()->create('spawns', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('map_id')->constrained('maps');
            });
            $connection->getSchemaBuilder()->enableForeignKeyConstraints();
        }
        config()->set('world_templates.update_tables', ['items', 'nodes', 'links', 'maps', 'spawns']);
        $this->source->table('maps')->insert(['id' => 11]);
        $this->source->table('spawns')->insert(['id' => 22, 'map_id' => 11]);
        $this->source->table('maps')->where('id', 11)->update(['spawn_id' => 22]);
        $this->apply($this->preview());
        $this->assertSame(22, $this->target->table('maps')->find(11)->spawn_id);
        $this->assertSame(11, $this->target->table('spawns')->find(22)->map_id);
    }

    public function test_large_diff_is_paginated_without_loading_snapshots_into_http_responses(): void
    {
        $this->source->table('items')->insert(array_map(fn (int $id): array => ['id' => $id, 'name' => 'Item '.$id], range(100, 1100)));
        $update = $this->preview();
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]))
            ->getJson(route('administration.world-template-updates.changes', ['update' => $update->id, 'table' => 'items', 'page' => 2]))
            ->assertOk()->assertJsonCount(30, 'data')->assertJsonPath('total', 1004)->assertJsonMissingPath('data.0.after_data');
        $this->apply($update);
        $this->assertSame(1003, $this->target->table('items')->count());
    }

    public function test_interrupted_operation_is_reconciled_and_old_snapshots_are_pruned(): void
    {
        $update = $this->preview();
        $this->apply($update);
        $update->refresh()->update(['status' => 'applying', 'updated_at' => now()->subHours(2)]);
        $this->artisan('world-template-updates:prune')->assertSuccessful();
        $this->assertSame('completed', $update->fresh()->status);
        $update->refresh()->update(['created_at' => now()->subDays(8)]);
        $this->artisan('world-template-updates:prune')->assertSuccessful();
        $this->assertTrue($update->fresh()->snapshot_pruned);
        $this->assertSame(0, $update->snapshotRows()->count());
        $this->assertSame('Nowy miecz', $this->target->table('items')->find(1)->name);
    }

    public function test_the_full_remote_schema_can_be_compared_and_applied_on_mysql(): void
    {
        if ($this->source->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires the optional Sail MySQL integration fixture.');
        }
        foreach (['update_source', 'update_target'] as $connection) {
            $this->assertSame(0, Artisan::call('migrate', [
                '--database' => $connection, '--path' => 'database/migrations/remote',
                '--force' => true, '--no-interaction' => true,
            ]), Artisan::output());
        }
        config()->set('world_templates.update_tables', $this->authoringTables);
        $this->assertEqualsCanonicalizing($this->authoringTables, array_values(array_diff($this->source->getSchemaBuilder()->getTableListing(), ['migrations', 'items', 'nodes', 'links'])));
        $update = $this->preview();
        $this->assertCount(count($this->authoringTables), $update->manifest);
        $this->apply($update);
        $this->assertSame('completed', $update->fresh()->status);
    }

    public function test_pending_dispatches_are_recovered_and_duplicate_jobs_do_not_repeat_the_operation(): void
    {
        $update = app(WorldTemplateUpdateService::class)->start($this->production, 1);
        $update->update(['updated_at' => now()->subMinutes(2)]);
        $this->artisan('world-template-updates:prune')->assertSuccessful();
        Queue::assertPushed(ProcessWorldTemplateUpdate::class, 2);
        $job = new ProcessWorldTemplateUpdate($update->id);
        $job->handle(app(WorldTemplateSnapshotService::class));
        $count = $update->snapshotRows()->count();
        $job->handle(app(WorldTemplateSnapshotService::class));
        $this->assertSame($count, $update->snapshotRows()->count());
        $this->assertSame('ready', $update->fresh()->status);
    }

    public function test_mysql_references_from_another_database_prevent_breaking_deletions(): void
    {
        if ($this->source->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires the optional Sail MySQL integration fixture.');
        }
        $database = 'testing_template_runtime_'.getmypid();
        DB::connection('update_test_admin')->getSchemaBuilder()->createDatabase($database);
        $this->mysqlDatabases[] = $database;
        config()->set('database.connections.update_test_runtime', [...config('database.connections.update_test_admin'), 'database' => $database]);
        $runtime = DB::connection('update_test_runtime');
        $runtime->getSchemaBuilder()->create('character_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on($this->target->getDatabaseName().'.items');
        });
        $runtime->table('character_items')->insert(['item_id' => 3]);
        try {
            $this->apply($this->preview());
            $this->fail('Expected cross-database foreign key rejection.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('character_items', $error->getMessage());
        } finally {
            DB::purge('update_test_runtime');
        }
        $this->assertNotNull($this->target->table('items')->find(3));
    }

    public function test_cleanup_resumes_when_a_pruned_snapshot_still_has_rows(): void
    {
        $update = $this->preview();
        $update->update(['snapshot_pruned' => true, 'created_at' => now()->subDays(8)]);
        $this->assertGreaterThan(0, $update->snapshotRows()->count());
        $this->artisan('world-template-updates:prune')->assertSuccessful();
        $this->assertSame(0, $update->snapshotRows()->count());
    }

    public function test_confirmed_administrator_requests_start_and_approve_the_update(): void
    {
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]));
        $response = $this->postJson(route('administration.world-template-updates.store', $this->production->id), ['confirmed' => true]);
        $response->assertStatus(202)->assertJsonPath('status', 'queued');
        $update = WorldTemplateUpdate::query()->findOrFail($response->json('id'));
        (new ProcessWorldTemplateUpdate($update->id))->handle(app(WorldTemplateSnapshotService::class));
        $this->postJson(route('administration.world-template-updates.approve', $update->id), ['confirmed' => true])
            ->assertStatus(202)->assertJsonPath('status', 'queued_apply');
        $this->assertSame('Stary miecz', $this->target->table('items')->find(1)->name);
    }

    public function test_diff_search_treats_wildcard_characters_as_literal_text(): void
    {
        $this->source->table('items')->where('id', 1)->update(['name' => 'Miecz_100%!']);
        $update = $this->preview();
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]))
            ->getJson(route('administration.world-template-updates.changes', ['update' => $update->id, 'search' => '_100%!']))
            ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('data.0.row_label', 'Miecz_100%! (id=1)');
    }

    private function migrationFixture(array $bodies): WorldTemplateMigrationService
    {
        $directory = storage_path('framework/template-migrations-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($directory);
        $this->migrationDirectories[] = $directory;
        $files = [];
        foreach ($bodies as $index => $body) {
            $name = '2026_10_19_'.str_pad((string) $index, 6, '0', STR_PAD_LEFT).'_fixture';
            $path = $directory.'/'.$name.'.php';
            File::put($path, '<?php return new class extends \\Illuminate\\Database\\Migrations\\Migration { public function up(): void { '.$body.' } };');
            $files[$name] = $path;
        }

        $service = new class(app(WorldTemplateSnapshotService::class), $files) extends WorldTemplateMigrationService
        {
            public function __construct(WorldTemplateSnapshotService $snapshots, private readonly array $files)
            {
                parent::__construct($snapshots);
            }

            protected function migrationFiles(): array
            {
                return $this->files;
            }
        };
        $this->instance(WorldTemplateMigrationService::class, $service);

        return $service;
    }

    private function addQualityMigration(): string
    {
        return '\\Illuminate\\Support\\Facades\\Schema::table("items", function (\\Illuminate\\Database\\Schema\\Blueprint $table): void { $table->string("quality")->nullable(); });';
    }

    public function test_migrations_publish_running_and_completed_steps_then_queue_a_fresh_comparison(): void
    {
        $migrations = $this->migrationFixture([$this->addQualityMigration()]);
        $update = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        $localConnection = DB::getDefaultConnection();
        $observed = [];
        DB::listen(function (QueryExecuted $query) use ($update, $localConnection, &$observed): void {
            if (in_array($query->connectionName, ['update_source', 'update_target'], true) && str_contains(strtolower($query->sql), 'alter table')) {
                $status = WorldTemplateUpdate::on($localConnection)->findOrFail($update->id)->publicStatus();
                $observed[] = collect($status['migration_steps'])->where('status', 'running')->count();
            }
        });
        (new ProcessWorldTemplateMigrations($update->id))->handle($migrations);
        $update->refresh();
        $this->assertSame('queued', $update->status);
        $this->assertSame($localConnection, DB::getDefaultConnection());
        $this->assertSame([1, 1], $observed);
        $this->assertCount(4, $update->migration_steps);
        $this->assertSame(['completed'], array_values(array_unique(array_column($update->migration_steps, 'status'))));
        $this->assertTrue($this->source->getSchemaBuilder()->hasColumn('items', 'quality'));
        $this->assertTrue($this->target->getSchemaBuilder()->hasColumn('items', 'quality'));
        $this->assertSame('Stary miecz', $this->target->table('items')->find(1)->name);
        Queue::assertPushed(ProcessWorldTemplateUpdate::class);
        (new ProcessWorldTemplateUpdate($update->id))->handle(app(WorldTemplateSnapshotService::class));
        $this->assertSame('ready', $update->fresh()->status);
        $this->assertCount(4, $update->fresh()->migration_steps);
    }

    public function test_only_pending_migrations_run_and_completed_files_are_not_repeated(): void
    {
        $migrations = $this->migrationFixture([$this->addQualityMigration()]);
        $update = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        (new ProcessWorldTemplateMigrations($update->id))->handle($migrations);
        (new ProcessWorldTemplateUpdate($update->id))->handle(app(WorldTemplateSnapshotService::class));
        $this->target->getSchemaBuilder()->table('items', fn (Blueprint $table) => $table->dropColumn('quality'));
        $this->target->table('migrations')->where('migration', '!=', '')->delete();
        $next = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        (new ProcessWorldTemplateMigrations($next->id))->handle($migrations);
        $this->assertCount(2, $next->fresh()->migration_steps);
        $this->assertSame('__current', $next->fresh()->migration_steps[0]['name']);
        $this->assertSame('target', $next->fresh()->migration_steps[1]['role']);
        $this->assertSame('completed', $next->fresh()->migration_steps[0]['status']);
        $this->assertSame('superseded', $update->fresh()->status);
    }

    public function test_migration_failure_preserves_completed_steps_stops_later_steps_and_restores_local_connection(): void
    {
        $migrations = $this->migrationFixture([$this->addQualityMigration(), 'throw new \\RuntimeException("Awaria testowej migracji");', 'throw new \\RuntimeException("Ta migracja nie może wystartować");']);
        $update = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        $local = DB::getDefaultConnection();
        try {
            (new ProcessWorldTemplateMigrations($update->id))->handle($migrations);
            $this->fail('Expected migration failure.');
        } catch (RuntimeException $error) {
            $this->assertSame('Awaria testowej migracji', $error->getMessage());
        }
        $this->assertSame($local, DB::getDefaultConnection());
        $update->refresh();
        $this->assertSame('failed', $update->status);
        $this->assertSame(['completed', 'completed', 'failed', 'pending'], array_slice(array_column($update->migration_steps, 'status'), 0, 4));
        $this->assertStringContainsString('Awaria testowej migracji', $update->migration_steps[2]['error']);
        $this->assertTrue($this->source->getSchemaBuilder()->hasColumn('items', 'quality'));
        $this->assertFalse($this->target->getSchemaBuilder()->hasColumn('items', 'quality'));
        $this->assertSame(1, $this->source->table('migrations')->count());
        Queue::assertNotPushed(ProcessWorldTemplateUpdate::class);
    }

    public function test_migrations_require_an_administrator_and_explicit_confirmation(): void
    {
        $url = route('administration.world-template-updates.migrate', $this->production->id);
        $this->actingAs(User::factory()->create())->postJson($url, ['confirmed' => true])->assertForbidden();
        $this->actingAs(User::factory()->create(['roles' => ['administrator']]))->postJson($url)->assertUnprocessable();
        $this->postJson($url, ['confirmed' => true])->assertStatus(202)->assertJsonPath('status', 'queued_migration');
        $this->postJson($url, ['confirmed' => true])->assertStatus(202);
        Queue::assertPushed(ProcessWorldTemplateMigrations::class, 1);
    }

    public function test_migrations_are_rejected_while_an_update_on_the_same_base_is_running(): void
    {
        app(WorldTemplateUpdateService::class)->start($this->production, 1);
        $this->expectException(ValidationException::class);
        app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
    }

    public function test_migrations_invalidate_previews_and_block_other_production_templates_sharing_the_base(): void
    {
        $preview = $this->preview();
        $anotherProduction = $this->makeTemplate('another_production', true, $this->base->id);
        app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        $this->assertSame('superseded', $preview->fresh()->status);
        $this->expectException(ValidationException::class);
        app(WorldTemplateUpdateService::class)->start($anotherProduction, 1);
    }

    public function test_timed_out_migrations_are_not_retried_automatically(): void
    {
        $update = WorldTemplateUpdate::query()->create([
            'target_template_id' => $this->production->id, 'source_template_id' => $this->base->id,
            'requested_by' => 1, 'status' => 'migrating',
            'migration_steps' => [['name' => 'interrupted', 'status' => 'running', 'error' => null]],
        ]);
        $update->update(['updated_at' => now()->subHours(2)]);
        $this->artisan('world-template-updates:prune')->assertSuccessful();
        $this->assertSame('failed', $update->fresh()->status);
        $this->assertSame('failed', $update->fresh()->migration_steps[0]['status']);
        Queue::assertNotPushed(ProcessWorldTemplateMigrations::class);
    }

    public function test_duplicate_migration_jobs_skip_the_already_processed_run(): void
    {
        $migrations = $this->migrationFixture([$this->addQualityMigration()]);
        $update = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        $update->update(['updated_at' => now()->subMinutes(2)]);
        $this->artisan('world-template-updates:prune')->assertSuccessful();
        Queue::assertPushed(ProcessWorldTemplateMigrations::class, 2);
        $job = new ProcessWorldTemplateMigrations($update->id);
        $job->handle($migrations);
        $steps = $update->fresh()->migration_steps;
        $job->handle($migrations);
        $this->assertSame($steps, $update->fresh()->migration_steps);
        $this->assertSame(1, $this->target->table('migrations')->count());
    }

    public function test_migration_timeout_records_failure_even_when_default_connection_is_remote(): void
    {
        $update = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        $job = new ProcessWorldTemplateMigrations($update->id);
        $update->update(['status' => 'migrating', 'migration_steps' => [['name' => 'interrupted', 'status' => 'running', 'error' => null]]]);
        $local = DB::getDefaultConnection();
        try {
            DB::setDefaultConnection('update_source');
            $job->failed(new RuntimeException('Przekroczono czas migracji.'));
        } finally {
            DB::setDefaultConnection($local);
        }
        $this->assertSame('failed', $update->fresh()->status);
        $this->assertSame('failed', $update->fresh()->migration_steps[0]['status']);
    }

    public function test_a_second_run_with_no_pending_migrations_queues_comparison_without_repeating_ddl(): void
    {
        $migrations = $this->migrationFixture([$this->addQualityMigration()]);
        $first = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        (new ProcessWorldTemplateMigrations($first->id))->handle($migrations);
        (new ProcessWorldTemplateUpdate($first->id))->handle(app(WorldTemplateSnapshotService::class));
        $second = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        (new ProcessWorldTemplateMigrations($second->id))->handle($migrations);
        $this->assertSame(['__current', '__current'], array_column($second->fresh()->migration_steps, 'name'));
        $this->assertSame(['completed', 'completed'], array_column($second->fresh()->migration_steps, 'status'));
        $this->assertSame('queued', $second->fresh()->status);
        $this->assertSame(1, $this->source->table('migrations')->count());
    }

    public function test_schema_drift_after_migrations_shows_details_and_preserves_the_migration_result(): void
    {
        $migrations = $this->migrationFixture([$this->addQualityMigration()]);
        $first = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        (new ProcessWorldTemplateMigrations($first->id))->handle($migrations);
        (new ProcessWorldTemplateUpdate($first->id))->handle(app(WorldTemplateSnapshotService::class));
        $this->target->getSchemaBuilder()->table('items', fn (Blueprint $table) => $table->dropColumn('quality'));
        $second = app(WorldTemplateUpdateService::class)->migrate($this->production, 1);
        (new ProcessWorldTemplateMigrations($second->id))->handle($migrations);
        $job = new ProcessWorldTemplateUpdate($second->id);
        try {
            $job->handle(app(WorldTemplateSnapshotService::class));
            $this->fail('Expected schema drift rejection.');
        } catch (RuntimeException $error) {
            $job->failed($error);
        }
        $status = $second->fresh()->publicStatus();
        $this->assertSame('failed', $status['status']);
        $this->assertStringContainsString('Sprawdzono migracje', $status['message']);
        $this->assertSame(['__current', '__current'], array_column($status['migration_steps'], 'name'));
        $this->assertCount(1, $status['schema_differences']);
        $this->assertSame('items', $status['schema_differences'][0]['table']);
        $this->assertSame('quality', $status['schema_differences'][0]['column']);
        $this->assertNull($status['schema_differences'][0]['target']);
        $this->assertSame('Stary miecz', $this->target->table('items')->where('id', 1)->value('name'));
    }
}

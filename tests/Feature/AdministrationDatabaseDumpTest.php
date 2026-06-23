<?php

namespace Tests\Feature;

use App\Enums\WorldType;
use App\Jobs\BuildDatabaseDumpJob;
use App\Models\User;
use App\Services\DatabaseDumpService;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdministrationDatabaseDumpTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $databaseDumpService = app(DatabaseDumpService::class);

        foreach (WorldType::getAll() as $world) {
            Cache::store('redis')->forget($databaseDumpService->statusKey($world));
        }
    }

    public function test_administrator_can_view_database_dump_page(): void
    {
        $response = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->get(route('administration.database-dumps.index'));

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Administration/DatabaseDumps')
                ->has('worlds', 4)
                ->where('worlds.0.value', 'retro')
                ->where('worlds.0.label', 'Retro')
                ->where('statuses.retro.status', 'idle')
            );
    }

    public function test_non_administrator_cannot_view_database_dump_page(): void
    {
        $response = $this
            ->actingAs($this->makeEditor())
            ->withSession(['world' => 'retro'])
            ->get(route('administration.database-dumps.index'));

        $response->assertForbidden();
    }

    public function test_non_administrator_cannot_download_database_dump(): void
    {
        $response = $this
            ->actingAs($this->makeEditor())
            ->withSession(['world' => 'retro'])
            ->get(route('administration.database-dumps.download', [
                'world' => 'retro',
                'dump' => 'dump-id',
            ]));

        $response->assertForbidden();
    }

    public function test_administrator_can_start_database_dump_job(): void
    {
        Bus::fake();

        $response = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->post(route('administration.database-dumps.start', ['world' => 'retro']));

        $response
            ->assertOk()
            ->assertJsonPath('world', 'retro')
            ->assertJsonPath('status', 'queued');

        $dumpId = $response->json('id');

        Bus::assertBatched(function (PendingBatch $batch): bool {
            /** @var BuildDatabaseDumpJob $job */
            $job = $batch->jobs->first();

            return $batch->name === 'Database dump: Retro'
                && $batch->jobs->count() === 1
                && $job instanceof BuildDatabaseDumpJob
                && $job->world === 'retro';
        });

        $retryResponse = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->post(route('administration.database-dumps.start', ['world' => 'retro']));

        $retryResponse
            ->assertOk()
            ->assertJsonPath('id', $dumpId)
            ->assertJsonPath('status', 'queued');

        Bus::assertBatchCount(1);
    }

    public function test_administrator_can_download_prepared_database_dump(): void
    {
        $databaseDumpService = app(DatabaseDumpService::class);
        $dumpId = 'testing-retro-dump';
        $path = storage_path('framework/testing-retro-dump.sql');
        file_put_contents($path, 'select 1;');

        Cache::store('redis')->put($databaseDumpService->statusKey('retro'), json_encode([
            'id' => $dumpId,
            'world' => 'retro',
            'status' => 'ready',
            'progress' => 100,
            'message' => 'Dump gotowy do pobrania',
            'filename' => 'retro-test.sql',
            'path' => $path,
            'dumped_bytes' => filesize($path),
            'estimated_bytes' => filesize($path),
            'file_size' => filesize($path),
            'requested_by' => 1,
            'queued_at' => now()->toIso8601String(),
            'started_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
            'finished_at' => now()->toIso8601String(),
            'failed_at' => null,
            'downloaded_at' => null,
            'expires_at' => now()->addDay()->toIso8601String(),
        ]), 3600);

        $response = $this
            ->actingAs($this->makeAdministrator())
            ->withSession(['world' => 'retro'])
            ->get(route('administration.database-dumps.download', [
                'world' => 'retro',
                'dump' => $dumpId,
            ]));

        $response
            ->assertOk()
            ->assertDownload('retro-test.sql')
            ->assertHeader('Content-Length', '9');

        $this->assertSame('select 1;', $response->streamedContent());
        $this->assertFileDoesNotExist($path);
        $this->assertSame(
            'downloaded',
            json_decode(Cache::store('redis')->get($databaseDumpService->statusKey('retro')), true)['status']
        );
    }

    public function test_ready_database_dump_uses_real_file_size_instead_of_previous_estimate(): void
    {
        $databaseDumpService = app(DatabaseDumpService::class);
        $result = $databaseDumpService->createRequest('retro', 1);
        $dumpId = (string) $result['status']['id'];
        $realFileSize = 44 * 1024 * 1024;

        $databaseDumpService->updateStatus('retro', $dumpId, [
            'status' => 'dumping',
            'progress' => 59,
            'dumped_bytes' => $realFileSize,
            'estimated_bytes' => 70 * 1024 * 1024,
        ]);

        $databaseDumpService->markReady('retro', $dumpId, $realFileSize);

        $status = $databaseDumpService->publicStatus('retro');

        $this->assertSame('ready', $status['status']);
        $this->assertSame(100, $status['progress']);
        $this->assertSame($realFileSize, $status['dumped_bytes']);
        $this->assertSame($realFileSize, $status['estimated_bytes']);
        $this->assertSame($realFileSize, $status['file_size']);
    }

    private function makeAdministrator(): User
    {
        return $this->makeUser([
            'login' => fake()->unique()->userName(),
            'name' => 'Administrator',
            'email' => fake()->unique()->safeEmail(),
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => [
                ['name' => 'administrator'],
            ],
            'permissions' => ['world.write'],
        ]);
    }

    private function makeEditor(): User
    {
        return $this->makeUser([
            'login' => fake()->unique()->userName(),
            'name' => 'Editor',
            'email' => fake()->unique()->safeEmail(),
            'forum_background_src' => null,
            'src' => 'retro/avatar.png',
            'roles' => [
                ['name' => 'game_master'],
            ],
            'permissions' => ['world.write'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeUser(array $attributes): User
    {
        $user = new User($attributes);
        $user->id = fake()->unique()->numberBetween(1, 100000);
        $user->exists = true;

        return $user;
    }
}

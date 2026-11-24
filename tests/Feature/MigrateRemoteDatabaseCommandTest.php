<?php

namespace Tests\Feature;

use App\Console\Commands\MigrateRemoteDatabase;
use App\Models\WorldTemplate;
use App\Services\DialogQuestAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class MigrateRemoteDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_option_migrates_each_active_world_template_connection(): void
    {
        WorldTemplate::query()->delete();

        config()->set('world_templates.remote_database_servers.testing', [
            'driver' => 'sqlite',
        ]);

        $alpha = WorldTemplate::query()->create($this->templateAttributes('Alpha', 'alpha', true));
        WorldTemplate::query()->create([
            ...$this->templateAttributes('Beta', 'beta', true),
            'is_visible' => false,
            'is_production' => true,
            'base_template_id' => $alpha->id,
        ]);
        WorldTemplate::query()->create($this->templateAttributes('Inactive', 'inactive', false));

        $command = new RecordingMigrateRemoteDatabase;
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--all' => true,
            '--force' => true,
            '--refresh-dialog-quest-availability' => true,
        ]);

        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);
        $this->assertSame([
            ['alpha_connection', 'migrate', true],
            ['beta_connection', 'migrate', true],
        ], $command->runs);
        $this->assertSame([
            'migrate:alpha_connection',
            'backfill:alpha_connection',
            'migrate:beta_connection',
            'backfill:beta_connection',
        ], $command->events);
    }

    public function test_world_slug_resolves_to_its_dynamic_connection(): void
    {
        WorldTemplate::query()->delete();

        config()->set('world_templates.remote_database_servers.testing', [
            'driver' => 'sqlite',
        ]);

        WorldTemplate::query()->create($this->templateAttributes('Alpha', 'alpha', true));

        $command = new RecordingMigrateRemoteDatabase;
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'connection' => 'alpha',
            'action' => 'rollback',
            '--force' => true,
        ]);

        $this->assertSame(SymfonyCommand::SUCCESS, $exitCode);
        $this->assertSame([
            ['alpha_connection', 'rollback', true],
        ], $command->runs);
    }

    /**
     * @return array<string, mixed>
     */
    private function templateAttributes(string $name, string $slug, bool $active): array
    {
        return [
            'name' => $name,
            'slug' => $slug,
            'connection_name' => "{$slug}_connection",
            'remote_database_server' => 'testing',
            'database_name' => "{$slug}.sqlite",
            'is_active' => $active,
            'is_visible' => true,
        ];
    }
}

class RecordingMigrateRemoteDatabase extends MigrateRemoteDatabase
{
    /** @var array<int, array{string, string, bool}> */
    public array $runs = [];

    public array $events = [];

    protected function refreshQuestAvailability(DialogQuestAvailabilityService $availability, string $connection): array
    {
        $this->events[] = "backfill:{$connection}";

        return [1, 0];
    }

    protected function runRemoteMigration(string $connection, string $action, bool $force): int
    {
        $this->runs[] = [$connection, $action, $force];
        $this->events[] = "{$action}:{$connection}";

        return SymfonyCommand::SUCCESS;
    }
}

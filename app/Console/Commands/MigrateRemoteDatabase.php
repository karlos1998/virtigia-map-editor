<?php

namespace App\Console\Commands;

use App\Models\WorldTemplate;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class MigrateRemoteDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:remote
                            {connection? : World template slug or database connection name}
                            {action=migrate : Action to run: migrate or rollback}
                            {--all : Run the action for every active world template}
                            {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations (or rollback) on remote database';

    /**
     * Execute the console command.
     */
    public function handle(WorldTemplateConnectionResolver $connectionResolver): int
    {
        $requestedConnection = $this->argument('connection');
        $action = (string) $this->argument('action');
        $all = (bool) $this->option('all');

        if (! in_array($action, ['migrate', 'rollback'], true)) {
            $this->components->error("Unsupported action [{$action}]. Use migrate or rollback.");

            return SymfonyCommand::FAILURE;
        }

        if ($all && $requestedConnection !== null) {
            $this->components->error('Pass either a connection or --all, not both.');

            return SymfonyCommand::FAILURE;
        }

        if (! $all && $requestedConnection === null) {
            $this->components->error('Pass a world template slug, a database connection name, or --all.');

            return SymfonyCommand::FAILURE;
        }

        $targets = $all
            ? $connectionResolver->templates()->filter(fn (WorldTemplate $template): bool => $template->is_active)
            : collect([$this->resolveRequestedTemplate($connectionResolver, (string) $requestedConnection)]);

        if ($targets->isEmpty()) {
            $this->components->error('No active world templates were found.');

            return SymfonyCommand::FAILURE;
        }

        foreach ($targets as $target) {
            if ($target instanceof WorldTemplate) {
                if (! $connectionResolver->registerTemplateConnection($target)) {
                    $this->components->error("Could not configure the database connection for world [{$target->slug}].");

                    return SymfonyCommand::FAILURE;
                }

                $slug = $target->slug;
                $connection = $target->connection_name;
            } else {
                $slug = (string) $target;
                $connection = (string) $target;
            }

            $this->components->info("Running remote {$action} for world [{$slug}] using connection [{$connection}].");

            if ($this->runRemoteMigration($connection, $action, (bool) $this->option('force')) !== SymfonyCommand::SUCCESS) {
                $this->components->error("Remote {$action} failed for world [{$slug}].");

                return SymfonyCommand::FAILURE;
            }
        }

        $this->components->info('Remote database migrations completed successfully.');

        return SymfonyCommand::SUCCESS;
    }

    protected function runRemoteMigration(string $connection, string $action, bool $force): int
    {
        $command = $action === 'rollback' ? 'migrate:rollback' : 'migrate';

        return $this->call($command, [
            '--database' => $connection,
            '--path' => 'database/migrations/remote',
            '--force' => $force,
            '--no-interaction' => true,
        ]);
    }

    private function resolveRequestedTemplate(
        WorldTemplateConnectionResolver $connectionResolver,
        string $requestedConnection,
    ): WorldTemplate|string {
        return $connectionResolver->resolve($requestedConnection) ?? $requestedConnection;
    }
}

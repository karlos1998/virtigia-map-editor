<?php

namespace App\Services;

use App\Jobs\ProcessWorldTemplateUpdate;
use App\Models\WorldTemplateUpdate;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class WorldTemplateMigrationService
{
    public function __construct(private readonly WorldTemplateSnapshotService $snapshots) {}

    public function run(WorldTemplateUpdate $update): void
    {
        $localConnection = $update->getConnection()->getName();
        $update->setConnection($localConnection);
        $originalDefault = DB::getDefaultConnection();

        try {
            [$source, $target] = $this->snapshots->connections($update);
            $files = $this->migrationFiles();
            $steps = [];
            $runners = [];

            foreach (['source' => $source, 'target' => $target] as $role => $connection) {
                $template = $role === 'source' ? $update->sourceTemplate : $update->targetTemplate;
                $repository = $this->repository($connection->getName());
                $firstStep = count($steps);
                $hasRepository = $repository->repositoryExists();
                $ran = $hasRepository ? $repository->getRan() : [];
                $migrator = new Migrator($repository, app('db'), new Filesystem, new Dispatcher(app()));
                $runners[$role] = ['connection' => $connection->getName(), 'migrator' => $migrator, 'repository' => $repository];
                if (! $hasRepository) {
                    $steps[] = $this->step($template->id, $template->name, $role, '__repository');
                }
                foreach ($files as $name => $path) {
                    if (! in_array($name, $ran, true)) {
                        $steps[] = $this->step($template->id, $template->name, $role, $name);
                    }
                }
                if (count($steps) === $firstStep) {
                    $steps[] = [...$this->step($template->id, $template->name, $role, '__current'), 'status' => 'completed'];
                }
            }
            $update->update(['migration_steps' => $steps, 'message' => 'Przygotowano listę brakujących migracji.']);

            foreach ($steps as $index => $step) {
                if ($step['status'] === 'completed') {
                    continue;
                }
                $runner = $runners[$step['role']];
                $steps[$index]['status'] = 'running';
                $steps[$index]['started_at'] = now()->toIso8601String();
                $update->update(['migration_steps' => $steps, 'message' => 'Migracja: '.$step['template_name'].' — '.$this->label($step['name'])]);
                try {
                    if ($step['name'] === '__repository') {
                        if (! $runner['repository']->repositoryExists()) {
                            $runner['repository']->createRepository();
                        }
                    } else {
                        $runner['migrator']->setConnection($runner['connection']);
                        try {
                            $runner['migrator']->run([$files[$step['name']]], ['step' => true]);
                        } finally {
                            DB::setDefaultConnection($originalDefault);
                        }
                        if (! in_array($step['name'], $runner['repository']->getRan(), true)) {
                            throw new RuntimeException('Migracja nie została zapisana w rejestrze: '.$step['name']);
                        }
                    }
                } catch (Throwable $error) {
                    $steps[$index]['status'] = 'failed';
                    $steps[$index]['error'] = mb_substr($error->getMessage(), 0, 1000);
                    $update->update(['migration_steps' => $steps]);
                    throw $error;
                }
                $steps[$index]['status'] = 'completed';
                $steps[$index]['completed_at'] = now()->toIso8601String();
                $update->update(['migration_steps' => $steps, 'progress' => (int) floor(($index + 1) / count($steps) * 100)]);
            }

            $update->update(['status' => 'queued', 'progress' => 0, 'message' => 'Migracje zakończone. Przygotowuję nowe porównanie danych.']);
            ProcessWorldTemplateUpdate::dispatch($update->id)->afterCommit();
        } catch (Throwable $error) {
            $this->markFailed($update, $error);
            throw $error;
        } finally {
            DB::setDefaultConnection($originalDefault);
        }
    }

    /** @return array<string, string> */
    protected function migrationFiles(): array
    {
        return app('migrator')->getMigrationFiles([database_path('migrations/remote')]);
    }

    private function repository(string $connection): DatabaseMigrationRepository
    {
        $configuration = config('database.migrations', 'migrations');
        $table = is_array($configuration) ? $configuration['table'] : $configuration;
        $repository = new DatabaseMigrationRepository(app('db'), $table);
        $repository->setSource($connection);

        return $repository;
    }

    /** @return array{template_id: int, template_name: string, role: string, name: string, status: string, error: null} */
    private function step(int $templateId, string $templateName, string $role, string $name): array
    {
        return ['template_id' => $templateId, 'template_name' => $templateName, 'role' => $role, 'name' => $name, 'status' => 'pending', 'error' => null];
    }

    private function label(string $name): string
    {
        return $name === '__repository' ? 'Przygotowanie rejestru migracji' : $name;
    }

    public function markFailed(WorldTemplateUpdate $update, Throwable $error): void
    {
        report($error);
        $steps = $update->fresh()->migration_steps ?? [];
        foreach ($steps as &$step) {
            if ($step['status'] === 'running') {
                $step['status'] = 'failed';
                $step['error'] = mb_substr($error->getMessage(), 0, 1000);
            }
        }
        unset($step);
        $update->update(['status' => 'failed', 'migration_steps' => $steps, 'message' => mb_substr('Migracje przerwane: '.$error->getMessage(), 0, 255)]);
    }
}

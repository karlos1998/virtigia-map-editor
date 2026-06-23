<?php

namespace App\Services;

use App\Models\WorldTemplate;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class WorldTemplateDatabaseService
{
    public function __construct(private readonly WorldTemplateConnectionResolver $connectionResolver) {}

    /**
     * @param  array{name: string, remote_database_server: string}  $attributes
     */
    public function createTemplate(array $attributes): WorldTemplate
    {
        $createdRemoteTemplate = null;

        try {
            return DB::transaction(function () use ($attributes, &$createdRemoteTemplate): WorldTemplate {
                $slug = $this->uniqueSlug((string) $attributes['name']);
                $databaseName = $this->databaseNameFor($slug);
                $connectionName = $slug;

                $template = WorldTemplate::query()->create([
                    'name' => trim((string) $attributes['name']),
                    'slug' => $slug,
                    'connection_name' => $connectionName,
                    'remote_database_server' => $attributes['remote_database_server'],
                    'database_name' => $databaseName,
                    'is_active' => true,
                    'is_visible' => true,
                ]);

                $this->createRemoteDatabase($template);
                $createdRemoteTemplate = $template;
                $this->connectionResolver->registerTemplateConnection($template);
                $this->runRemoteMigrations($template);

                return $template;
            });
        } catch (Throwable $throwable) {
            if ($createdRemoteTemplate instanceof WorldTemplate) {
                $this->dropRemoteDatabaseAfterFailure($createdRemoteTemplate);
            }

            throw $throwable;
        }
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name, '_');

        if ($baseSlug === '') {
            throw new InvalidArgumentException('Nazwa template musi zawierać litery albo cyfry.');
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (WorldTemplate::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}_{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function databaseNameFor(string $slug): string
    {
        $databaseName = config('world_templates.database_prefix', 'template_').$slug;

        if (! preg_match('/^[A-Za-z0-9_]+$/', $databaseName)) {
            throw new InvalidArgumentException('Wygenerowana nazwa bazy zawiera niedozwolone znaki.');
        }

        return $databaseName;
    }

    private function createRemoteDatabase(WorldTemplate $template): void
    {
        $server = $this->connectionResolver->remoteDatabaseServer($template->remote_database_server);

        if ($server === null) {
            throw new InvalidArgumentException("Nie znaleziono konfiguracji zdalnej bazy [{$template->remote_database_server}].");
        }

        $driver = (string) ($server['driver'] ?? '');

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $this->createMySqlDatabase($template, $server);

            return;
        }

        if ($driver === 'sqlite') {
            $this->createSqliteDatabase($template);

            return;
        }

        throw new InvalidArgumentException("Tworzenie template'u nie obsługuje drivera [{$driver}].");
    }

    private function dropRemoteDatabaseAfterFailure(WorldTemplate $template): void
    {
        try {
            $server = $this->connectionResolver->remoteDatabaseServer($template->remote_database_server);

            if ($server === null) {
                return;
            }

            $driver = (string) ($server['driver'] ?? '');

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $this->dropMySqlDatabase($template, $server);

                return;
            }

            if ($driver === 'sqlite') {
                File::delete(database_path($template->database_name));
            }
        } catch (Throwable $cleanupThrowable) {
            report($cleanupThrowable);
        }
    }

    /**
     * @param  array<string, mixed>  $server
     */
    private function createMySqlDatabase(WorldTemplate $template, array $server): void
    {
        $connection = "world_template_server_{$template->remote_database_server}";
        $databaseName = $this->quoteIdentifier($template->database_name);
        $charset = (string) ($server['charset'] ?? 'utf8mb4');
        $collation = (string) ($server['collation'] ?? 'utf8mb4_unicode_ci');

        config()->set("database.connections.{$connection}", [
            ...$server,
            'database' => null,
        ]);

        try {
            DB::connection($connection)->statement(
                "CREATE DATABASE {$databaseName} CHARACTER SET {$charset} COLLATE {$collation}"
            );
        } finally {
            DB::purge($connection);
        }
    }

    /**
     * @param  array<string, mixed>  $server
     */
    private function dropMySqlDatabase(WorldTemplate $template, array $server): void
    {
        $connection = "world_template_server_{$template->remote_database_server}";
        $databaseName = $this->quoteIdentifier($template->database_name);

        config()->set("database.connections.{$connection}", [
            ...$server,
            'database' => null,
        ]);

        try {
            DB::connection($connection)->statement("DROP DATABASE IF EXISTS {$databaseName}");
        } finally {
            DB::purge($connection);
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new InvalidArgumentException('Nazwa bazy zawiera niedozwolone znaki.');
        }

        return '`'.$identifier.'`';
    }

    private function createSqliteDatabase(WorldTemplate $template): void
    {
        $path = database_path($template->database_name);

        if (File::exists($path)) {
            throw new InvalidArgumentException("Baza [{$template->database_name}] już istnieje.");
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, '');
    }

    private function runRemoteMigrations(WorldTemplate $template): void
    {
        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => $template->connection_name,
                '--path' => 'database/migrations/remote',
                '--force' => true,
                '--no-interaction' => true,
            ]);
        } catch (Throwable $throwable) {
            throw new RuntimeException($throwable->getMessage(), previous: $throwable);
        }

        if ($exitCode !== 0) {
            throw new RuntimeException(Artisan::output() ?: 'Migracje zdalnej bazy zakończyły się błędem.');
        }
    }
}

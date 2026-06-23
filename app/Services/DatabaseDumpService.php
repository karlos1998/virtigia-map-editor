<?php

namespace App\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\DbDumper\Databases\MariaDb;
use Spatie\DbDumper\Databases\MySql;
use Spatie\DbDumper\Databases\Sqlite;
use Spatie\DbDumper\DbDumper;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseDumpService
{
    private const int CACHE_TTL_SECONDS = 86400;

    private const string STATUS_PREFIX = 'database_dump';

    /**
     * @return array{status: array<string, mixed>, created: bool}
     */
    public function createRequest(string $world, int $userId): array
    {
        $this->validateWorld($world);

        try {
            return Cache::store('redis')
                ->lock($this->statusKey($world).'_lock', 10)
                ->block(5, fn (): array => $this->createLockedRequest($world, $userId));
        } catch (LockTimeoutException) {
            return [
                'status' => $this->status($world),
                'created' => false,
            ];
        }
    }

    /**
     * @return array{status: array<string, mixed>, created: bool}
     */
    private function createLockedRequest(string $world, int $userId): array
    {
        $currentStatus = $this->status($world);

        if ($this->isReusableStatus($currentStatus)) {
            return [
                'status' => $currentStatus,
                'created' => false,
            ];
        }

        $this->deleteStatusFile($currentStatus);

        $id = (string) Str::uuid();
        $filename = sprintf('%s-%s.sql', $world, now()->format('Ymd-His'));
        $path = $this->dumpDirectory().DIRECTORY_SEPARATOR.$id.'.sql';

        $status = [
            'id' => $id,
            'world' => $world,
            'status' => 'queued',
            'progress' => 0,
            'message' => 'Czeka w kolejce',
            'filename' => $filename,
            'path' => $path,
            'dumped_bytes' => 0,
            'estimated_bytes' => null,
            'file_size' => 0,
            'requested_by' => $userId,
            'queued_at' => now()->toIso8601String(),
            'started_at' => null,
            'updated_at' => now()->toIso8601String(),
            'finished_at' => null,
            'failed_at' => null,
            'downloaded_at' => null,
            'expires_at' => now()->addSeconds(self::CACHE_TTL_SECONDS)->toIso8601String(),
        ];

        $this->putStatus($world, $status);

        return [
            'status' => $status,
            'created' => true,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function publicStatuses(): array
    {
        return collect(app(WorldTemplateConnectionResolver::class)->visibleSlugs())
            ->mapWithKeys(fn (string $world): array => [
                $world => $this->publicStatus($world),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicStatus(string $world): array
    {
        $this->validateWorld($world);

        return $this->sanitizeStatus($this->status($world), $world);
    }

    /**
     * @return array{path: string, filename: string, size: int}
     */
    public function readyDump(string $world, string $dumpId): array
    {
        $this->validateWorld($world);

        $status = $this->status($world);

        if (($status['id'] ?? null) !== $dumpId || ($status['status'] ?? null) !== 'ready') {
            throw new InvalidArgumentException('Dump nie jest gotowy do pobrania.');
        }

        $path = (string) ($status['path'] ?? '');

        if ($path === '' || ! File::exists($path)) {
            $this->putStatus($world, [
                ...$status,
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Przygotowany plik wygasł albo został usunięty.',
                'failed_at' => now()->toIso8601String(),
                'updated_at' => now()->toIso8601String(),
            ]);

            throw new InvalidArgumentException('Dump nie istnieje.');
        }

        return [
            'path' => $path,
            'filename' => (string) ($status['filename'] ?? basename($path)),
            'size' => File::size($path),
        ];
    }

    public function markDownloaded(string $world, string $dumpId): void
    {
        $status = $this->status($world);

        if (($status['id'] ?? null) !== $dumpId) {
            return;
        }

        $this->putStatus($world, [
            ...$status,
            'status' => 'downloaded',
            'message' => 'Plik został pobrany',
            'downloaded_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateStatus(string $world, string $dumpId, array $attributes): void
    {
        $status = $this->status($world);

        if (($status['id'] ?? null) !== $dumpId) {
            return;
        }

        $this->putStatus($world, [
            ...$status,
            ...$attributes,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function failStatus(string $world, string $dumpId, Throwable $throwable): void
    {
        $status = $this->status($world);

        if (($status['id'] ?? null) !== $dumpId) {
            return;
        }

        $this->putStatus($world, [
            ...$status,
            'status' => 'failed',
            'progress' => 0,
            'message' => 'Nie udało się przygotować dumpa.',
            'error' => $throwable->getMessage(),
            'failed_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ]);

        $this->deleteStatusFile($status);
    }

    public function markReady(string $world, string $dumpId, int $fileSize): void
    {
        $this->updateStatus($world, $dumpId, [
            'status' => 'ready',
            'progress' => 100,
            'message' => 'Dump gotowy do pobrania',
            'dumped_bytes' => $fileSize,
            'estimated_bytes' => $fileSize,
            'file_size' => $fileSize,
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    public function estimateWorldSize(string $world): ?int
    {
        $connection = $this->connectionConfig($world);
        $driver = (string) ($connection['driver'] ?? '');

        try {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $database = (string) ($connection['database'] ?? '');

                $row = DB::connection($this->connectionName($world))->selectOne(
                    'select coalesce(sum(data_length), 0) as bytes from information_schema.tables where table_schema = ?',
                    [$database]
                );

                $bytes = (int) ($row->bytes ?? 0);

                return $bytes > 0 ? $bytes : null;
            }

            if ($driver === 'sqlite') {
                $database = (string) ($connection['database'] ?? '');

                if (! File::exists($database)) {
                    return null;
                }

                $bytes = File::size($database);

                return $bytes > 0 ? $bytes : null;
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * @return array{process: Process, credentialHandle?: resource}
     */
    public function createDumpProcess(string $world, string $path): array
    {
        $dumper = $this->makeDumper($this->connectionConfig($world));

        if ($dumper instanceof MySql) {
            $credentialHandle = tmpfile();

            if ($credentialHandle === false) {
                throw new InvalidArgumentException('Nie udało się utworzyć tymczasowego pliku z poświadczeniami bazy.');
            }

            $dumper->setTempFileHandle($credentialHandle);
            $process = $dumper->getProcess($path);
            fflush($credentialHandle);

            return [
                'process' => $process,
                'credentialHandle' => $credentialHandle,
            ];
        }

        if ($dumper instanceof Sqlite) {
            return [
                'process' => $dumper->getProcess($path),
            ];
        }

        throw new InvalidArgumentException('Unsupported database dumper.');
    }

    public function ensureDumpProcessSucceeded(Process $process, string $path): void
    {
        if (! $process->isSuccessful()) {
            throw new InvalidArgumentException($process->getErrorOutput() ?: 'Proces dumpowania zakończył się błędem.');
        }

        if (! File::exists($path)) {
            throw new InvalidArgumentException('Proces dumpowania nie utworzył pliku.');
        }

        if (File::size($path) === 0) {
            throw new InvalidArgumentException('Proces dumpowania utworzył pusty plik.');
        }
    }

    public function progressFromBytes(int $dumpedBytes, ?int $estimatedBytes): ?int
    {
        if ($estimatedBytes === null || $estimatedBytes <= 0) {
            return null;
        }

        return min(95, max(1, (int) round(($dumpedBytes / $estimatedBytes) * 95)));
    }

    public function validateWorld(string $world): void
    {
        if (! in_array($world, app(WorldTemplateConnectionResolver::class)->visibleSlugs(), true)) {
            throw new InvalidArgumentException("Unsupported world [{$world}].");
        }
    }

    public function statusKey(string $world): string
    {
        return self::STATUS_PREFIX."_{$world}_status";
    }

    /**
     * @return array<string, mixed>
     */
    private function status(string $world): array
    {
        $status = json_decode(Cache::store('redis')->get($this->statusKey($world)) ?? '{}', true);

        return is_array($status) ? $status : [];
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function putStatus(string $world, array $status): void
    {
        Cache::store('redis')->put($this->statusKey($world), json_encode($status), self::CACHE_TTL_SECONDS);
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array<string, mixed>
     */
    private function sanitizeStatus(array $status, string $world): array
    {
        if ($status === []) {
            return [
                'id' => null,
                'world' => $world,
                'status' => 'idle',
                'progress' => 0,
                'message' => 'Gotowy do przygotowania',
                'filename' => null,
                'dumped_bytes' => 0,
                'estimated_bytes' => null,
                'file_size' => 0,
                'queued_at' => null,
                'started_at' => null,
                'updated_at' => null,
                'finished_at' => null,
                'failed_at' => null,
                'downloaded_at' => null,
                'expires_at' => null,
            ];
        }

        return collect($status)
            ->except(['path', 'requested_by', 'error'])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function isReusableStatus(array $status): bool
    {
        $statusName = $status['status'] ?? null;

        if (in_array($statusName, ['queued', 'estimating', 'dumping'], true)) {
            return true;
        }

        if ($statusName === 'ready') {
            $path = (string) ($status['path'] ?? '');

            return $path !== '' && File::exists($path);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function deleteStatusFile(array $status): void
    {
        $path = (string) ($status['path'] ?? '');

        if ($path !== '') {
            File::delete($path);
        }
    }

    private function dumpDirectory(): string
    {
        $directory = storage_path('app/database-dumps');
        File::ensureDirectoryExists($directory);

        return $directory;
    }

    /**
     * @return array<string, mixed>
     */
    private function connectionConfig(string $world): array
    {
        $this->validateWorld($world);
        $connectionName = $this->connectionName($world);

        /** @var array<string, mixed>|null $connection */
        $connection = config("database.connections.{$connectionName}");

        if ($connection === null) {
            throw new InvalidArgumentException("Database connection [{$connectionName}] is not configured.");
        }

        return $connection;
    }

    private function connectionName(string $world): string
    {
        return app(WorldTemplateConnectionResolver::class)->connectionNameFor($world) ?? $world;
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function makeDumper(array $connection): DbDumper
    {
        $driver = (string) ($connection['driver'] ?? '');

        return match ($driver) {
            'mysql' => $this->hasMariaDbDumpBinary()
                ? $this->makeMariaDbDumper($connection)
                : $this->makeMySqlDumper($connection),
            'mariadb' => $this->makeMariaDbDumper($connection),
            'sqlite' => $this->makeSqliteDumper($connection),
            default => throw new InvalidArgumentException("Unsupported database driver [{$driver}]."),
        };
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function makeMySqlDumper(array $connection): MySql
    {
        $dumper = MySql::create();
        $this->configureMySqlDumper($dumper, $connection);

        return $dumper;
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function makeMariaDbDumper(array $connection): MariaDb
    {
        $dumper = MariaDb::create();

        $mariaDbDumpBinaryPath = $this->mariaDbDumpBinaryPath();

        if ($mariaDbDumpBinaryPath !== null) {
            $dumper->setDumpBinaryPath(dirname($mariaDbDumpBinaryPath));
        }

        $this->configureMySqlDumper($dumper, $connection);

        return $dumper;
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function makeSqliteDumper(array $connection): Sqlite
    {
        return Sqlite::create()
            ->setDbName((string) ($connection['database'] ?? ''))
            ->setTimeout(0);
    }

    /**
     * @param  array<string, mixed>  $connection
     */
    private function configureMySqlDumper(MySql $dumper, array $connection): void
    {
        $dumper
            ->setHost((string) ($connection['host'] ?? ''))
            ->setPort((int) ($connection['port'] ?? 3306))
            ->setDbName((string) ($connection['database'] ?? ''))
            ->setUserName((string) ($connection['username'] ?? ''))
            ->setPassword((string) ($connection['password'] ?? ''))
            ->setDefaultCharacterSet((string) ($connection['charset'] ?? 'utf8mb4'))
            ->setTimeout(0)
            ->useSingleTransaction()
            ->skipLockTables()
            ->useQuick()
            ->addExtraOption('--routines')
            ->addExtraOption('--events')
            ->addExtraOption('--triggers');

        if (! $dumper instanceof MariaDb) {
            $dumper
                ->doNotUseColumnStatistics()
                ->setGtidPurged('OFF')
                ->addExtraOption('--no-tablespaces');
        }

        $socket = (string) ($connection['unix_socket'] ?? '');

        if ($socket !== '') {
            $dumper->setSocket($socket);
        }
    }

    private function hasMariaDbDumpBinary(): bool
    {
        return $this->mariaDbDumpBinaryPath() !== null;
    }

    private function mariaDbDumpBinaryPath(): ?string
    {
        foreach (['/usr/bin/mariadb-dump', '/usr/local/bin/mariadb-dump'] as $path) {
            if (is_executable($path)) {
                return $path;
            }
        }

        return null;
    }
}

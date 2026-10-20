<?php

namespace App\Services;

use App\Jobs\RunAssetOperation;
use App\Rules\AssetPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use ZipArchive;

class AssetOperationService
{
    /** @param array{path: string, type: string, operation: string, destination?: string|null} $data */
    public function start(int $userId, array $data): array
    {
        app(AssetManagerService::class)->validateOperation($data);
        $id = (string) Str::uuid();
        $this->save($id, [
            ...$data,
            'id' => $id,
            'user_id' => $userId,
            'status' => 'queued',
            'progress' => 0,
            'processed' => 0,
            'total' => null,
            'message' => 'Oczekuje w kolejce',
            'created_at' => now()->toIso8601String(),
        ]);
        Cache::lock('asset-manager:recent:'.$userId, 10)->block(5, function () use ($userId, $id): void {
            $key = 'asset-manager:recent:'.$userId;
            Cache::put($key, array_slice([$id, ...Cache::get($key, [])], 0, 30), now()->addHours(config('asset_manager.operation_lifetime_hours')));
        });
        try {
            RunAssetOperation::dispatch($id);
        } catch (Throwable $exception) {
            $this->fail($id, $exception);
            throw $exception;
        }

        return $this->status($id, $userId);
    }

    public function status(string $id, int $userId): array
    {
        $status = Cache::get($this->key($id));
        abort_unless(is_array($status) && $status['user_id'] === $userId, 404);
        unset($status['user_id']);
        $status['download_url'] = $status['status'] === 'completed' && $status['operation'] === 'archive'
            ? route('administration.assets.operations.download', ['operation' => $id]) : null;

        return $status;
    }

    public function recent(int $userId): array
    {
        $statuses = [];
        foreach (Cache::get('asset-manager:recent:'.$userId, []) as $id) {
            if (Cache::has($this->key($id))) {
                $statuses[] = $this->status($id, $userId);
            }
        }

        return $statuses;
    }

    public function run(string $id): void
    {
        $data = Cache::get($this->key($id));
        if (! is_array($data) || $data['status'] !== 'queued') {
            return;
        }
        $this->update($id, ['status' => 'running', 'message' => 'Przygotowuję operację']);
        try {
            if ($data['operation'] === 'archive') {
                $this->archive($id, $data['path']);
            } else {
                app(AssetManagerService::class)->mutate($data, fn (int $done, int $total) => $this->progress($id, $done, $total));
            }
            $this->update($id, ['status' => 'completed', 'progress' => 100, 'message' => 'Gotowe']);
        } catch (Throwable $exception) {
            $this->fail($id, $exception);
            throw $exception;
        }
    }

    private function archive(string $id, string $path): void
    {
        $manager = app(AssetManagerService::class);
        $manager->requireDirectory($path);
        $local = Storage::disk('local');
        $directory = 'asset-manager/'.$id;
        if (! $local->makeDirectory($directory.'/parts')) {
            throw new RuntimeException('Nie udało się utworzyć katalogu tymczasowego.');
        }
        $zip = new ZipArchive;
        $opened = false;
        try {
            $opened = $zip->open($local->path($directory.'/archive.zip'), ZipArchive::CREATE | ZipArchive::OVERWRITE) === true;
            if (! $opened || ! $zip->addEmptyDir(basename($path))) {
                throw new RuntimeException('Nie udało się utworzyć archiwum ZIP.');
            }
            $entries = $manager->disk()->getDriver()->listContents($path, true)->toArray();
            foreach ($entries as $index => $entry) {
                $relative = basename($path).'/'.substr($entry->path(), strlen($path) + 1);
                if (! AssetPath::isValid($relative)) {
                    throw new RuntimeException('Archiwum zawiera nieprawidłową ścieżkę.');
                }
                if ($entry->isDir()) {
                    $added = $zip->addEmptyDir($relative);
                } else {
                    $stream = $manager->disk()->readStream($entry->path());
                    if (! is_resource($stream)) {
                        throw new RuntimeException('Nie udało się odczytać pliku z S3.');
                    }
                    $part = $directory.'/parts/'.$index;
                    try {
                        if (! $local->put($part, $stream)) {
                            throw new RuntimeException('Nie udało się zapisać pliku tymczasowego.');
                        }
                    } finally {
                        fclose($stream);
                    }
                    $added = $zip->addFile($local->path($part), $relative);
                }
                if (! $added) {
                    throw new RuntimeException('Nie udało się dodać pliku do archiwum.');
                }
                $this->progress($id, $index + 1, count($entries));
            }
            $this->update($id, ['message' => 'Kończę zapisywanie ZIP']);
            $closed = $zip->close();
            $opened = false;
            if (! $closed) {
                throw new RuntimeException('Nie udało się zapisać archiwum ZIP.');
            }
        } finally {
            if ($opened) {
                $zip->close();
            }
            $local->deleteDirectory($directory.'/parts');
        }
    }

    private function progress(string $id, int $processed, int $total): void
    {
        $this->update($id, [
            'processed' => $processed,
            'total' => $total,
            'progress' => $total > 0 ? min(99, (int) floor($processed / $total * 100)) : 0,
            'message' => "Przetworzono {$processed} z {$total}",
        ]);
    }

    public function fail(string $id, Throwable $exception): void
    {
        $this->update($id, [
            'status' => 'failed',
            'message' => $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->first()
                : 'Operacja nie została ukończona. Odśwież zawartość i sprawdź wynik przed ponowieniem.',
        ]);
        Storage::disk('local')->deleteDirectory('asset-manager/'.$id);
    }

    public function download(string $id, int $userId): BinaryFileResponse
    {
        $status = $this->status($id, $userId);
        abort_unless($status['operation'] === 'archive' && $status['status'] === 'completed', 409);
        $path = Storage::disk('local')->path('asset-manager/'.$id.'/archive.zip');
        abort_unless(is_file($path), 410, 'Archiwum wygasło. Przygotuj je ponownie.');

        return response()->download($path, basename($status['path']).'.zip', [
            'Content-Type' => 'application/zip', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function update(string $id, array $changes): void
    {
        $data = Cache::get($this->key($id));
        if (is_array($data)) {
            $this->save($id, [...$data, ...$changes]);
        }
    }

    private function save(string $id, array $data): void
    {
        Cache::put($this->key($id), $data, now()->addHours(config('asset_manager.operation_lifetime_hours')));
    }

    private function key(string $id): string
    {
        return 'asset-manager:operation:'.$id;
    }
}

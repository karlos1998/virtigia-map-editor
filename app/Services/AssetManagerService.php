<?php

namespace App\Services;

use App\Rules\AssetPath;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\StorageAttributes;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetManagerService
{
    private const IMAGE_TYPES = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'bmp'];

    public function disk(): FilesystemAdapter
    {
        return Storage::disk('s3');
    }

    public static function maxUploadBytes(): int
    {
        $limits = [(int) config('asset_manager.max_upload_kb') * 1024];
        foreach (['upload_max_filesize', 'post_max_size'] as $setting) {
            $limit = ini_parse_quantity(ini_get($setting));
            if ($limit > 0) {
                $limits[] = $limit;
            }
        }

        return min($limits);
    }

    /** @return array{entries: array, total: int, page: int, per_page: int} */
    public function browse(string $path, string $search, int $page, bool $directoriesOnly): array
    {
        $this->requireDirectory($path);
        $entries = [];
        $needle = mb_strtolower($search);
        foreach ($this->disk()->getDriver()->listContents($path, $search !== '') as $entry) {
            if ($directoriesOnly && ! $entry->isDir()) {
                continue;
            }
            if ($needle !== '' && ! str_contains(mb_strtolower(basename($entry->path())), $needle)) {
                continue;
            }
            $entries[$entry->path()] = $entry;
        }
        usort($entries, fn (StorageAttributes $a, StorageAttributes $b): int => ($b->isDir() <=> $a->isDir()) ?: strnatcasecmp($a->path(), $b->path()));

        $perPage = (int) config('asset_manager.per_page');
        $visible = $directoriesOnly ? $entries : array_slice($entries, ($page - 1) * $perPage, $perPage);

        return [
            'entries' => array_map(fn (StorageAttributes $entry): array => $this->describe($entry), $visible),
            'total' => count($entries),
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return array{path: string, name: string, type: string, size: ?int, modified: ?int, is_image: bool, preview_url: ?string, thumbnail_url: ?string} */
    private function describe(StorageAttributes $entry): array
    {
        $image = $entry->isFile() && in_array(strtolower(pathinfo($entry->path(), PATHINFO_EXTENSION)), self::IMAGE_TYPES, true);

        return [
            'path' => $entry->path(),
            'name' => basename($entry->path()),
            'type' => $entry->isDir() ? 'directory' : 'file',
            'size' => $entry->isFile() ? $entry->fileSize() : null,
            'modified' => $entry->lastModified(),
            'is_image' => $image,
            'preview_url' => $image ? route('administration.assets.preview', ['path' => $entry->path(), 'version' => $entry->lastModified()]) : null,
            'thumbnail_url' => $image ? route('administration.assets.thumbnail', ['path' => $entry->path(), 'version' => $entry->lastModified()]) : null,
        ];
    }

    public function requireDirectory(string $path): void
    {
        abort_unless($path === '' || $this->disk()->directoryExists($path), 404, 'Katalog nie istnieje.');
    }

    public function requireEntry(string $path, string $type): void
    {
        abort_if($path === '', 422, 'Wybierz plik lub katalog.');
        abort_unless($type === 'directory' ? $this->disk()->directoryExists($path) : $this->disk()->fileExists($path), 404, 'Plik lub katalog nie istnieje.');
    }

    public function ensureAvailable(string $path): void
    {
        if ($this->disk()->fileExists($path) || $this->disk()->directoryExists($path)) {
            throw ValidationException::withMessages(['destination' => 'Plik lub katalog o tej nazwie już istnieje. Nic nie zostało nadpisane.']);
        }
        $this->requireDirectory(dirname($path) === '.' ? '' : dirname($path));
    }

    public function createDirectory(string $parent, string $name): void
    {
        $this->write(function () use ($parent, $name): void {
            $this->requireDirectory($parent);
            $path = ltrim($parent.'/'.$name, '/');
            Validator::make(['path' => $path], ['path' => new AssetPath])->validate();
            $this->ensureAvailable($path);
            $this->check($this->disk()->makeDirectory($path));
        });
    }

    public function upload(string $parent, UploadedFile $file): void
    {
        $this->write(function () use ($parent, $file): void {
            $name = $file->getClientOriginalName();
            Validator::make(['file' => $name], ['file' => new AssetPath(singleSegment: true)])->validate();
            $this->requireDirectory($parent);
            $path = ltrim($parent.'/'.$name, '/');
            Validator::make(['path' => $path], ['path' => new AssetPath])->validate();
            $this->ensureAvailable($path);
            $this->check($this->disk()->putFileAs($parent, $file, $name) !== false);
        });
    }

    public function fileResponse(string $path, bool $preview): StreamedResponse
    {
        $this->requireEntry($path, 'file');
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if (! $preview) {
            return $this->disk()->download($path, basename($path), $headers + ['Content-Type' => 'application/octet-stream']);
        }
        $mime = $this->disk()->mimeType($path);
        abort_unless(in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/avif', 'image/bmp', 'image/x-ms-bmp'], true), 415, 'Ten plik nie obsługuje podglądu.');

        return $this->disk()->response($path, basename($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    /** @param array{path: string, type: string, operation: string, destination?: string|null} $data */
    public function validateOperation(array $data): void
    {
        $this->requireEntry($data['path'], $data['type']);
        if (in_array($data['operation'], ['copy', 'move'], true)) {
            $destination = $data['destination'];
            if ($destination === $data['path'] || str_starts_with($destination, $data['path'].'/')) {
                throw ValidationException::withMessages(['destination' => 'Wybierz inne miejsce, poza przenoszonym katalogiem.']);
            }
            $this->ensureAvailable($destination);
        }
        if ($data['operation'] === 'archive' && $data['type'] !== 'directory') {
            throw ValidationException::withMessages(['type' => 'Archiwum ZIP tworzymy dla katalogu.']);
        }
    }

    /** @param array{path: string, type: string, operation: string, destination?: string|null} $data */
    public function mutate(array $data, Closure $progress): void
    {
        $this->write(function () use ($data, $progress): void {
            $this->validateOperation($data);
            $source = $data['path'];
            if ($data['operation'] === 'delete') {
                $this->check($data['type'] === 'directory' ? $this->disk()->deleteDirectory($source) : $this->disk()->delete($source));
                $progress(1, 1);

                return;
            }
            $destination = $data['destination'];
            $copiedFiles = [];
            $directories = [];
            if ($data['type'] === 'file') {
                $checksum = $this->disk()->checksum($source);
                $this->check($this->disk()->copy($source, $destination));
                $copiedFiles[$source] = ['source' => $checksum, 'destination' => $this->disk()->checksum($destination)];
            } else {
                $entries = $this->disk()->getDriver()->listContents($source, true)->toArray();
                $directories[] = $source;
                $this->check($this->disk()->makeDirectory($destination));
                foreach ($entries as $index => $entry) {
                    $target = $destination.substr($entry->path(), strlen($source));
                    if ($entry->isDir()) {
                        $directories[] = $entry->path();
                        $this->check($this->disk()->makeDirectory($target));
                    } else {
                        $checksum = $this->disk()->checksum($entry->path());
                        $this->check($this->disk()->copy($entry->path(), $target));
                        $copiedFiles[$entry->path()] = ['source' => $checksum, 'destination' => $this->disk()->checksum($target)];
                    }
                    $progress($index + 1, count($entries));
                }
            }
            if ($data['operation'] === 'move') {
                foreach ($copiedFiles as $path => $checksums) {
                    $target = $destination.substr($path, strlen($source));
                    if (! is_string($checksums['source']) || ! is_string($checksums['destination']) || $this->disk()->checksum($path) !== $checksums['source'] || $this->disk()->checksum($target) !== $checksums['destination']) {
                        throw ValidationException::withMessages(['path' => 'Zawartość zmieniła się podczas kopiowania. Źródło i przygotowana kopia zostały zachowane.']);
                    }
                }
                foreach ($copiedFiles as $path => $checksums) {
                    if ($this->disk()->checksum($path) !== $checksums['source']) {
                        throw ValidationException::withMessages(['path' => 'Plik zmienił się podczas przenoszenia i nie został usunięty. Sprawdź źródło i kopię.']);
                    }
                    $this->check($this->disk()->delete($path));
                }
                usort($directories, fn (string $a, string $b): int => strlen($b) <=> strlen($a));
                foreach ($directories as $directory) {
                    $this->removeDirectoryMarker($directory);
                }
                if ($data['type'] === 'directory' && $this->disk()->directoryExists($source)) {
                    throw ValidationException::withMessages(['path' => 'Skopiowano wybrane pliki. Nowe pliki dodane w trakcie operacji pozostawiono w katalogu źródłowym.']);
                }
            }
            $progress(1, 1);
        }, 3600);
    }

    private function removeDirectoryMarker(string $path): void
    {
        $disk = $this->disk();
        if ($disk instanceof AwsS3V3Adapter) {
            $config = $disk->getConfig();
            $disk->getClient()->deleteObject([
                'Bucket' => $config['bucket'],
                'Key' => ltrim(($config['root'] ?? '').'/'.$path.'/', '/'),
            ]);
        } elseif ($disk->getAdapter() instanceof LocalFilesystemAdapter) {
            @rmdir($disk->path($path));
        }
    }

    private function write(Closure $callback, int $seconds = 3600): void
    {
        try {
            Cache::lock('asset-manager:write', $seconds)->block(5, $callback);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['path' => 'Trwa inna operacja zapisu. Poczekaj na jej zakończenie i spróbuj ponownie.']);
        }
    }

    private function check(bool $success): void
    {
        if (! $success) {
            throw new RuntimeException('Magazyn S3 nie potwierdził wykonania operacji.');
        }
    }
}

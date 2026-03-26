<?php

namespace App\Support;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Storage;

final class AssetUrlGenerator
{
    public function npc(?string $path): ?string
    {
        return $this->forDirectory(config('assets.dirs.npcs'), $path);
    }

    public function map(?string $path): ?string
    {
        return $this->forDirectory(config('assets.dirs.maps'), $path);
    }

    public function item(?string $path): ?string
    {
        return $this->forDirectory(config('assets.dirs.items'), $path);
    }

    public function outfit(?string $path): ?string
    {
        return $this->forDirectory(config('assets.dirs.outfits'), $path);
    }

    public function fromPath(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return $this->signPath($this->normalizePath($path));
    }

    private function forDirectory(string $directory, ?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return $this->signPath($this->joinPath($directory, $path));
    }

    private function signPath(string $path): string
    {
        $expiration = now()->addMinutes((int) config('assets.temporary_url_lifetime', 60));
        $diskConfig = config('filesystems.disks.s3');
        $temporaryUrlBase = $diskConfig['temporary_url'] ?? $diskConfig['url'] ?? $diskConfig['endpoint'] ?? null;

        if (blank($temporaryUrlBase)) {
            return Storage::disk('s3')->temporaryUrl($path, $expiration);
        }

        $client = new S3Client([
            'version' => 'latest',
            'region' => $diskConfig['region'],
            'credentials' => [
                'key' => $diskConfig['key'],
                'secret' => $diskConfig['secret'],
            ],
            'bucket_endpoint' => false,
            'use_path_style_endpoint' => $diskConfig['use_path_style_endpoint'] ?? false,
            'endpoint' => rtrim($temporaryUrlBase, '/'),
        ]);

        $command = $client->getCommand('GetObject', [
            'Bucket' => $diskConfig['bucket'],
            'Key' => $path,
        ]);

        return (string) $client->createPresignedRequest($command, $expiration)->getUri();
    }

    private function joinPath(string $directory, string $path): string
    {
        return trim($directory, '/').'/'.ltrim($this->normalizePath($path), '/');
    }

    private function normalizePath(string $path): string
    {
        $normalizedPath = str_replace('imgimg', 'img', trim($path));

        if (filter_var($normalizedPath, FILTER_VALIDATE_URL)) {
            $normalizedPath = parse_url($normalizedPath, PHP_URL_PATH) ?: '';
        }

        return ltrim(strtok($normalizedPath, '?') ?: '', '/');
    }
}

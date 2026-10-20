<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetThumbnailService
{
    public function response(string $path): BinaryFileResponse
    {
        $manager = app(AssetManagerService::class);
        $manager->requireEntry($path, 'file');
        $disk = $manager->disk();
        $checksum = $disk->checksum($path);
        abort_unless(is_string($checksum), 415);
        $cached = 'asset-thumbnails/'.hash('sha256', $path.'|'.$checksum).'.png';
        $local = Storage::disk('local');
        if (! $local->exists($cached)) {
            $stream = $disk->readStream($path);
            abort_unless(is_resource($stream), 404);
            try {
                $contents = stream_get_contents($stream, 32 * 1024 * 1024 + 1);
            } finally {
                fclose($stream);
            }
            abort_unless(is_string($contents) && strlen($contents) <= 32 * 1024 * 1024, 415);
            $info = @getimagesizefromstring($contents);
            abort_unless($info && $info[0] > 0 && $info[1] > 0 && $info[0] * $info[1] <= 24000000, 415);
            $source = @imagecreatefromstring($contents);
            unset($contents);
            abort_unless($source !== false, 415);
            $ratio = min(1, 320 / max($info[0], $info[1]));
            $thumbnail = imagecreatetruecolor(max(1, (int) round($info[0] * $ratio)), max(1, (int) round($info[1] * $ratio)));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $temporary = 'asset-thumbnails/'.Str::uuid().'.part';
            $local->makeDirectory('asset-thumbnails');
            try {
                imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, imagesx($thumbnail), imagesy($thumbnail), $info[0], $info[1]);
                if (! imagepng($thumbnail, $local->path($temporary)) || ! $local->move($temporary, $cached)) {
                    throw new RuntimeException('Nie udało się zapisać miniatury.');
                }
            } finally {
                imagedestroy($source);
                imagedestroy($thumbnail);
                $local->delete($temporary);
            }
        }

        return response()->file($local->path($cached), [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

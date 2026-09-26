<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class AssetService
{
    public function search(string $path, bool $onlyImages = true)
    {
        $directories = Storage::disk('s3')->directories($path);
        $files = Storage::disk('s3')->files($path);

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $items = collect($directories)->map(fn ($dir) => [
            'path' => $dir,
            'type' => 'dir',
        ])->merge(
            collect($files)
                ->when($onlyImages, function ($files) use ($imageExtensions) {
                    return $files->filter(function ($file) use ($imageExtensions) {
                        $extension = pathinfo($file, PATHINFO_EXTENSION);

                        return in_array(strtolower($extension), $imageExtensions);
                    });
                })
                ->map(fn ($file) => [
                    'path' => $file,
                    'type' => 'file',
                ])
        );

        return $items->all();
    }

    public function storeFromBase64(string $prefix, string $base64Image, string $fileName): array
    {
        $replace = substr($base64Image, 0, strpos($base64Image, ',') + 1);
        $imageData = str_replace($replace, '', $base64Image);
        $imageData = str_replace(' ', '+', $imageData);

        $decodedImage = base64_decode($imageData);

        if ($decodedImage === false) {
            throw new \Exception('Failed to decode base64 image data.');
        }

        return $this->store($prefix, $decodedImage, $fileName);
    }

    /**
     * @return array{url: string, width: int, height: int}
     */
    public function store(string $prefix, string $contents, string $fileName): array
    {
        $filePath = $prefix.$fileName;

        if (Storage::disk('s3')->exists($filePath)) {
            throw ValidationException::withMessages([
                'fileName' => 'The file name is already taken.',
            ]);
        }

        $result = Storage::disk('s3')->put($filePath, $contents);

        if (! $result) {
            throw new \Exception('Failed to upload the image to S3.');
        }

        $image = imagecreatefromstring($contents);
        if (! $image) {
            throw new \Exception('Failed to create image from decoded data.');
        }

        $width = imagesx($image);
        $height = imagesy($image);

        imagedestroy($image);

        return [
            'url' => Storage::disk('s3')->url($filePath),
            'width' => $width,
            'height' => $height,
        ];
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

final class AssetService
{
    public function search(string $path, bool $onlyImages = true)
    {
        $directories = Storage::disk('s3')->directories($path);
        $files = Storage::disk('s3')->files($path);

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $items = collect($directories)->map(fn($dir) => [
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
                ->map(fn($file) => [
                    'path' => $file,
                    'type' => 'file',
                ])
        );

        return $items->all();
    }
}

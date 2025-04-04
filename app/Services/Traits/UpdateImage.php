<?php

namespace App\Services\Traits;

use App\Models\BaseNpc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

trait UpdateImage {
    public function updateImageFromBase64(Model $model, Stringable $base64, Stringable $name, string $prefix)
    {
        $currentSrc = str_replace("$prefix/", '', $model->src);
        $parts = explode('/', $currentSrc, 3);
        $baseFolder = isset($parts[2]) ? "{$parts[0]}/{$parts[1]}/" : "$parts[0]/";

        preg_match('/^data:image\/(png|gif);base64,/', $base64, $matches);
        $extension = $matches[1] ?? 'png';

        $imageData = substr($base64, strpos($base64, ',') + 1);
        $decodedImage = base64_decode($imageData);

        $fileName = $name->isNotEmpty() ? Str::slug(pathinfo($name->value(), PATHINFO_FILENAME)) : Str::uuid();
        $storagePath = "$prefix/{$baseFolder}";
        $filePath = "{$storagePath}{$fileName}.{$extension}";

        if (Storage::disk('s3')->exists($filePath)) {
            $fileName = Str::uuid() . "-{$fileName}";
            $filePath = "{$storagePath}{$fileName}.{$extension}";
        }

//        dd($model->src, "$prefix/", $currentSrc, $parts, $baseFolder, $filePath, str_replace("$prefix/", '', $filePath));

        Storage::disk('s3')->put($filePath, $decodedImage);

        $model->src = str_replace("$prefix/", '', $filePath);
        $model->save();
    }
}

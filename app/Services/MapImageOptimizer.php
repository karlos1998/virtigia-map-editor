<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;

final class MapImageOptimizer
{
    /**
     * @return array{contents: string, width: int, height: int, original_size: int, optimized_size: int}
     */
    public function optimize(UploadedFile $file): array
    {
        $originalContents = file_get_contents($file->getRealPath());

        if ($originalContents === false) {
            throw new RuntimeException('Nie udało się odczytać przesłanej grafiki mapy.');
        }

        $imageInfo = getimagesizefromstring($originalContents);
        $image = imagecreatefromstring($originalContents);

        if ($imageInfo === false || $image === false) {
            throw new RuntimeException('Nie udało się przetworzyć przesłanej grafiki mapy.');
        }

        [$width, $height] = $imageInfo;
        $mimeType = $imageInfo['mime'] ?? null;

        $outputBufferLevel = ob_get_level();
        ob_start();

        try {
            $encoded = match ($mimeType) {
                'image/jpeg' => $this->encodeJpeg($image),
                'image/png' => $this->encodePng($image),
                default => false,
            };
            $optimizedContents = ob_get_clean();
        } finally {
            imagedestroy($image);

            if (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }
        }

        if (! $encoded || ! is_string($optimizedContents)) {
            throw new RuntimeException('Nie udało się zoptymalizować grafiki mapy.');
        }

        $contents = strlen($optimizedContents) < strlen($originalContents)
            ? $optimizedContents
            : $originalContents;

        return [
            'contents' => $contents,
            'width' => $width,
            'height' => $height,
            'original_size' => strlen($originalContents),
            'optimized_size' => strlen($contents),
        ];
    }

    private function encodeJpeg(\GdImage $image): bool
    {
        imageinterlace($image, true);

        return imagejpeg($image, null, 85);
    }

    private function encodePng(\GdImage $image): bool
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return imagepng($image, null, 9, PNG_ALL_FILTERS);
    }
}

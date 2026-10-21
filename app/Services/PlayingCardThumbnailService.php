<?php

namespace App\Services;

use App\Models\BaseItem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PlayingCardThumbnailService
{
    /** @param array<string, mixed> $card */
    public function render(array $card): string
    {
        $image = imagecreatetruecolor(32, 32);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        $border = $card['hero'] ? imagecolorallocate($image, 229, 184, 66) : imagecolorallocate($image, 164, 151, 120);
        $blue = imagecolorallocate($image, 47, 78, 118);
        $ink = imagecolorallocate($image, 239, 229, 201);
        imagefilledrectangle($image, 6, 1, 25, 30, $border);
        imagefilledrectangle($image, 8, 3, 23, 27, $blue);
        imagefilledrectangle($image, 8, 25, 23, 27, $ink);
        imagesetthickness($image, 2);
        $row = $card['rows'][0] ?? null;
        if ($card['kind'] === 'unit' && $row === 'close') {
            imageline($image, 11, 11, 21, 21, $ink);
            imageline($image, 11, 21, 21, 11, $ink);
            imageline($image, 10, 17, 14, 21, $ink);
            imageline($image, 18, 21, 22, 17, $ink);
        } elseif ($card['kind'] === 'unit' && $row === 'ranged') {
            imagearc($image, 14, 16, 14, 16, 270, 90, $ink);
            imageline($image, 14, 8, 14, 24, $ink);
            imageline($image, 10, 16, 22, 16, $ink);
        } elseif ($card['kind'] === 'unit' && $row === 'siege') {
            imageellipse($image, 12, 22, 5, 5, $ink);
            imageellipse($image, 21, 22, 5, 5, $ink);
            imageline($image, 11, 19, 22, 19, $ink);
            imageline($image, 15, 18, 20, 9, $ink);
            imageline($image, 18, 8, 22, 10, $ink);
        } else {
            imagefilledpolygon($image, [16, 8, 22, 16, 16, 23, 10, 16], $ink);
        }
        if ($card['kind'] === 'unit' && count($card['rows']) > 1) {
            imagefilledrectangle($image, 8, 3, 11, 7, $ink);
        }
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        if (! is_string($png)) {
            throw new RuntimeException('Nie udało się wygenerować miniatury karty.');
        }

        return $png;
    }

    /** @param array<string, mixed> $card */
    public function save(BaseItem $item, array $card): void
    {
        $path = 'img/playing-cards/thumbnails/'.Str::uuid().'.png';
        if (! Storage::disk('s3')->put($path, $this->render($card))) {
            throw new RuntimeException('Nie udało się zapisać miniatury karty.');
        }
        $item->update(['src' => substr($path, 4)]);
    }
}

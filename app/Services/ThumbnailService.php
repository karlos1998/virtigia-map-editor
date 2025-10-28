<?php

namespace App\Services;

use App\Models\Map;
use Illuminate\Support\Facades\Storage;

class ThumbnailService
{
    /**
     * Generate thumbnail for a map
     *
     * @param Map $map
     * @param string|null $world World connection name (if null, will derive from map src)
     * @return bool
     */
    public function generateThumbnail(Map $map, ?string $world = null): bool
    {
        $disk = Storage::disk('s3');

        // original src stored like `retro/filename.png` -> actual S3 key is under img/locations/
        $srcPath = trim($map->src, '/');
        $originalKey = 'img/locations/' . $srcPath;

        if (!$disk->exists($originalKey)) {
            return false;
        }

        // derive thumbnail prefix: e.g. "retro" -> "retro-thumbnail"
        $parts = explode('/', $srcPath);
        $prefix = $parts[0] ?? ($world ?? 'thumbnail');
        $thumbFolder = $prefix . '-thumbnail';

        $baseName = pathinfo($srcPath, PATHINFO_FILENAME);

        $thumbFileName = $baseName . '-' . now()->timestamp;

        // model should store without img/locations/ prefix
        $modelThumbPath = $thumbFolder . '/' . $thumbFileName; // extension appended later

        try {
            $contents = $disk->get($originalKey);
        } catch (\Exception $e) {
            return false;
        }

        $srcImage = @imagecreatefromstring($contents);
        if (!$srcImage) {
            return false;
        }

        $srcW = imagesx($srcImage);
        $srcH = imagesy($srcImage);

        // thumbnail size = original / 32
        $newW = max(1, (int)floor($srcW / 32));
        $newH = max(1, (int)floor($srcH / 32));

        $thumb = imagecreatetruecolor($newW, $newH);

        // Try to preserve transparency for PNG/GIF
        $info = @getimagesizefromstring($contents);
        $mime = $info[2] ?? null;
        if ($mime === IMAGETYPE_PNG) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
            imagefilledrectangle($thumb, 0, 0, $newW, $newH, $transparent);
        } elseif ($mime === IMAGETYPE_GIF) {
            $transparentIndex = imagecolortransparent($srcImage);
            $colorsTotal = imagecolorstotal($srcImage);
            if ($transparentIndex >= 0 && $transparentIndex < $colorsTotal) {
                // only attempt to read transparent color if index is valid for this palette
                $transparentColor = imagecolorsforindex($srcImage, $transparentIndex);
                if (is_array($transparentColor)) {
                    $transIndex = imagecolorallocate($thumb, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                    imagefill($thumb, 0, 0, $transIndex);
                    imagecolortransparent($thumb, $transIndex);
                }
            }
        }

        imagecopyresampled($thumb, $srcImage, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

        // determine output format
        $extension = 'jpg';
        if ($mime === IMAGETYPE_PNG) {
            ob_start();
            imagepng($thumb);
            $thumbContents = ob_get_clean();
            $extension = 'png';
        } elseif ($mime === IMAGETYPE_GIF) {
            ob_start();
            imagegif($thumb);
            $thumbContents = ob_get_clean();
            $extension = 'gif';
        } else {
            ob_start();
            imagejpeg($thumb, null, 85);
            $thumbContents = ob_get_clean();
            $extension = 'jpg';
        }

        imagedestroy($srcImage);
        imagedestroy($thumb);

        $modelThumbPath .= '.' . $extension;

        // ensure folder exists when using local driver; for s3, put will create keys
        try {
            $disk->put('img/locations/' . $modelThumbPath, $thumbContents, ['visibility' => 'public']);
            $map->thumbnail_src = $modelThumbPath;
            $map->save();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

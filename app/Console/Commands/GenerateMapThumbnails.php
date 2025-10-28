<?php

namespace App\Console\Commands;

use App\Models\DynamicModel;
use App\Models\Map;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateMapThumbnails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Command generates thumbnails always at size original/32 and stores them under
     * img/locations/{prefix}-thumbnail/{filename}. The model stores path without the
     * `img/locations/` prefix, e.g. `retro-thumbnail/filename.png`.
     */
    protected $signature = 'maps:generate-thumbnails {--world= : The world connection name (e.g. retro)} {--force}';

    /**
     * The console command description.
     */
    protected $description = 'Generate thumbnails for maps from their `src` on S3 and save to given folder if missing.';

    public function handle(): int
    {
        $world = $this->option('world');

        if (!$world) {
            $this->error('The --world option is required (e.g. --world=retro)');
            return 1;
        }

        DynamicModel::setGlobalConnection($world);

        $force = (bool)$this->option('force');

        $disk = Storage::disk('s3');

        $query = Map::query();
        if (!$force) {
            $query->whereNull('thumbnail_src');
        }

        $query->chunkById(100, function ($maps) use ($disk, $force) {
            foreach ($maps as $map) {
                // original src stored like `retro/filename.png` -> actual S3 key is under img/locations/
                $srcPath = trim($map->src, '/');
                $originalKey = 'img/locations/' . $srcPath;

                if (!$disk->exists($originalKey)) {
                    $this->warn("Original not found: {$originalKey} (map id {$map->id})");
                    continue;
                }

                // derive thumbnail prefix: e.g. "retro" -> "retro-thumbnail"
                $parts = explode('/', $srcPath);
                $prefix = $parts[0] ?? 'thumbnail';
                $thumbFolder = $prefix . '-thumbnail';

                $baseName = pathinfo($srcPath, PATHINFO_FILENAME);
                $origExt = pathinfo($srcPath, PATHINFO_EXTENSION) ?: 'png';

                $thumbFileName = $baseName . '-' . now()->timestamp;

                // model should store without img/locations/ prefix
                $modelThumbPath = $thumbFolder . '/' . $thumbFileName; // extension appended later

                // if model already has thumbnail and file exists on disk, skip
                if (!$force && $map->thumbnail_src) {
                    $existingDiskKey = 'img/locations/' . ltrim($map->thumbnail_src, '/');
                    if ($disk->exists($existingDiskKey)) {
                        $this->info("Thumbnail exists (model): {$map->thumbnail_src} for map id {$map->id}");
                        continue;
                    }
                }

                try {
                    $contents = $disk->get($originalKey);
                } catch (\Exception $e) {
                    $this->error("Failed to download {$originalKey}: " . $e->getMessage());
                    continue;
                }

                $srcImage = @imagecreatefromstring($contents);
                if (!$srcImage) {
                    $this->error("Unsupported image or corrupted data for {$originalKey}");
                    continue;
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
                    $this->info("Saved thumbnail for map id {$map->id} -> {$modelThumbPath}");
                } catch (\Exception $e) {
                    $this->error("Failed to upload thumbnail for map id {$map->id}: " . $e->getMessage());
                }
            }
        });

        return 0;
    }
}

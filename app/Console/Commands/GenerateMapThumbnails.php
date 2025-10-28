<?php

namespace App\Console\Commands;

use App\Models\DynamicModel;
use App\Models\Map;
use App\Services\ThumbnailService;
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

    public function __construct(
        private readonly ThumbnailService $thumbnailService
    )
    {
        parent::__construct();
    }

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

        $query->chunkById(100, function ($maps) use ($disk, $force, $world) {
            foreach ($maps as $map) {
                // Check if original exists
                $srcPath = trim($map->src, '/');
                $originalKey = 'img/locations/' . $srcPath;

                if (!$disk->exists($originalKey)) {
                    $this->warn("Original not found: {$originalKey} (map id {$map->id})");
                    continue;
                }

                // if model already has thumbnail and file exists on disk, skip (unless force)
                if (!$force && $map->thumbnail_src) {
                    $existingDiskKey = 'img/locations/' . ltrim($map->thumbnail_src, '/');
                    if ($disk->exists($existingDiskKey)) {
                        $this->info("Thumbnail exists (model): {$map->thumbnail_src} for map id {$map->id}");
                        continue;
                    }
                }

                $success = $this->thumbnailService->generateThumbnail($map, $world);

                if ($success) {
                    $this->info("Saved thumbnail for map id {$map->id} -> {$map->thumbnail_src}");
                } else {
                    $this->error("Failed to generate thumbnail for map id {$map->id}");
                }
            }
        });

        return 0;
    }
}

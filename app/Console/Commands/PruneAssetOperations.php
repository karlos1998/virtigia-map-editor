<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneAssetOperations extends Command
{
    protected $signature = 'assets:prune-operations';

    protected $description = 'Usuń wygasłe archiwa i pliki tymczasowe menedżera grafik';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $cutoff = now()->subHours(config('asset_manager.operation_lifetime_hours'))->timestamp;
        foreach ($disk->directories('asset-manager') as $directory) {
            $newest = 0;
            foreach ($disk->getDriver()->listContents($directory, true) as $entry) {
                $newest = max($newest, $entry->lastModified() ?? 0);
            }
            if ($newest < $cutoff) {
                $disk->deleteDirectory($directory);
            }
        }
        foreach ($disk->getDriver()->listContents('asset-thumbnails') as $entry) {
            if ($entry->isFile() && ($entry->lastModified() ?? 0) < $cutoff) {
                $disk->delete($entry->path());
            }
        }

        return self::SUCCESS;
    }
}

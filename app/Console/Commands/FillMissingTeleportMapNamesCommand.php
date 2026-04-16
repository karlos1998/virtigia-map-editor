<?php

namespace App\Console\Commands;

use App\Jobs\FillMissingTeleportMapNamesForWorldJob;
use Illuminate\Console\Command;

class FillMissingTeleportMapNamesCommand extends Command
{
    protected $signature = 'base-items:fill-missing-teleport-map-names
        {world : World name or "all"}';

    protected $description = 'Dispatch jobs that fill missing map names in base item teleport attributes';

    public function handle(): int
    {
        $world = (string) $this->argument('world');
        $worlds = $world === 'all' ? ['retro', 'legacy'] : [$world];

        foreach ($worlds as $selectedWorld) {
            if (! in_array($selectedWorld, ['retro', 'legacy'], true)) {
                $this->error("Unsupported world [$selectedWorld]. Allowed values: retro, legacy, all.");

                return self::FAILURE;
            }
        }

        foreach ($worlds as $selectedWorld) {
            FillMissingTeleportMapNamesForWorldJob::dispatch($selectedWorld);
        }

        $this->info('Dispatched missing teleport map names fill job for: '.implode(', ', $worlds));

        return self::SUCCESS;
    }
}

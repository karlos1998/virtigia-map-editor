<?php

namespace App\Console\Commands;

use App\Jobs\RefreshBaseItemDuplicateViewJob;
use Illuminate\Console\Command;

class RefreshBaseItemDuplicateViewCommand extends Command
{
    protected $signature = 'base-item-duplicates:refresh
        {world : World name or "all"}';

    protected $description = 'Dispatch refresh jobs for potential duplicate base items';

    public function handle(): int
    {
        $world = (string) $this->argument('world');
        $worlds = $world === 'all' ? ['retro', 'legacy'] : [$world];

        foreach ($worlds as $selectedWorld) {
            if (! in_array($selectedWorld, ['retro', 'legacy'], true)) {
                $this->error("Unsupported world [{$selectedWorld}]. Allowed values: retro, legacy, all.");

                return self::FAILURE;
            }

            RefreshBaseItemDuplicateViewJob::dispatch($selectedWorld);
            $this->info("Dispatched base item duplicate refresh for [{$selectedWorld}].");
        }

        return self::SUCCESS;
    }
}

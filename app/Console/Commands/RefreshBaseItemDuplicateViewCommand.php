<?php

namespace App\Console\Commands;

use App\Jobs\RefreshBaseItemDuplicateViewJob;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Console\Command;

class RefreshBaseItemDuplicateViewCommand extends Command
{
    protected $signature = 'base-item-duplicates:refresh
        {world : World name or "all"}';

    protected $description = 'Dispatch refresh jobs for potential duplicate base items';

    public function handle(): int
    {
        $world = (string) $this->argument('world');
        $availableWorlds = app(WorldTemplateConnectionResolver::class)->visibleSlugs();
        $worlds = $world === 'all' ? $availableWorlds : [$world];

        foreach ($worlds as $selectedWorld) {
            if (! in_array($selectedWorld, $availableWorlds, true)) {
                $this->error('Unsupported world ['.$selectedWorld.']. Allowed values: '.implode(', ', $availableWorlds).', all.');

                return self::FAILURE;
            }

            RefreshBaseItemDuplicateViewJob::dispatch($selectedWorld);
            $this->info("Dispatched base item duplicate refresh for [{$selectedWorld}].");
        }

        return self::SUCCESS;
    }
}

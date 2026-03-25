<?php

namespace App\Console\Commands;

use App\Jobs\RefreshBaseItemUsageViewBatchJob;
use Illuminate\Console\Command;

class RefreshBaseItemUsageViewCommand extends Command
{
    protected $signature = 'base-item-usage-view:refresh
        {world : World name or "all"}
        {--chunk-size=500 : Number of base items per chunk job}';

    protected $description = 'Dispatch refresh jobs for base item usage cache for the selected world';

    public function handle(): int
    {
        $world = (string) $this->argument('world');
        $chunkSize = max(1, (int) $this->option('chunk-size'));
        $worlds = $world === 'all' ? ['retro', 'legacy'] : [$world];

        foreach ($worlds as $selectedWorld) {
            if (! in_array($selectedWorld, ['retro', 'legacy'], true)) {
                $this->error("Unsupported world [{$selectedWorld}]. Allowed values: retro, legacy, all.");

                return self::FAILURE;
            }

            RefreshBaseItemUsageViewBatchJob::dispatch($selectedWorld, $chunkSize);
            $this->info("Dispatched base item usage refresh for [{$selectedWorld}] with chunk size {$chunkSize}.");
        }

        return self::SUCCESS;
    }
}

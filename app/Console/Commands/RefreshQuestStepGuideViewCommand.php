<?php

namespace App\Console\Commands;

use App\Jobs\RefreshQuestStepGuideViewBatchJob;
use Illuminate\Console\Command;

class RefreshQuestStepGuideViewCommand extends Command
{
    protected $signature = 'quest-step-guides:refresh
        {world : World name or "all"}
        {--chunk-size=20 : Number of quest steps per chunk job}';

    protected $description = 'Dispatch refresh jobs for cached quest step guides';

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

            RefreshQuestStepGuideViewBatchJob::dispatch($selectedWorld, $chunkSize);
            $this->info("Dispatched quest step guides refresh for [{$selectedWorld}] with chunk size {$chunkSize}.");
        }

        return self::SUCCESS;
    }
}

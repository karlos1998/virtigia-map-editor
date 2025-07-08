<?php

namespace App\Console\Commands;

use App\Jobs\ResetAggressiveNpcsJob;
use Illuminate\Console\Command;

class ResetAggressiveNpcsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-aggressive-npcs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset is_aggressive flag to false for all NPCs (not MOBs)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to reset aggressive NPCs...');

        // Dispatch the job synchronously for immediate execution
        dispatch_sync(new ResetAggressiveNpcsJob());

        $this->info('Aggressive NPCs have been reset successfully.');

        return Command::SUCCESS;
    }
}

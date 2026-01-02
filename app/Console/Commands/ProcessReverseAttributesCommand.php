<?php

namespace App\Console\Commands;

use App\Jobs\ProcessReverseAttributesBatchJob;
use App\Models\BaseItem;
use App\Models\DynamicModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class ProcessReverseAttributesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverse-attributes:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process reverse attributes for BaseItems that have attributes but no attribute points';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DynamicModel::setGlobalConnection('retro');
        $this->info('Finding BaseItems that need reverse attribute processing...');

        $query = BaseItem::whereNull('attribute_points')
            ->whereNull('manual_attribute_points')
            ->whereNull('reverse_attributes')
            ->whereNotNull('attributes');

        $count = $query->count();

        if ($count === 0) {
            $this->info('No BaseItems found that need reverse attribute processing.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} BaseItems to process.");

        // Create batch jobs in chunks of 100
        $batchJobs = [];
        $query->chunk(100, function ($baseItems) use (&$batchJobs) {
            $batchJobs[] = new ProcessReverseAttributesBatchJob($baseItems);
        });

        $this->info('Creating batch job with ' . count($batchJobs) . ' batch(es)...');

        $batch = Bus::batch($batchJobs)
            ->name('Process Reverse Attributes')
            ->dispatch();

        $this->info('Batch created with ID: ' . $batch->id);

        return Command::SUCCESS;
    }
}

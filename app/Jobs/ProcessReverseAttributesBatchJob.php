<?php

namespace App\Jobs;

use App\Jobs\ProcessReverseAttributesItemJob;
use Illuminate\Bus\Batchable;
use App\Models\DynamicModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class ProcessReverseAttributesBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout

    /**
     * Create a new job instance.
     */
    public function __construct(
        private Collection $baseItems
    )
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DynamicModel::setGlobalConnection('retro');

        // Create individual jobs for each base item
        $itemJobs = $this->baseItems->map(function ($baseItem) {
            return new ProcessReverseAttributesItemJob($baseItem->id);
        });

        // Dispatch as a sub-batch
        Bus::batch($itemJobs)
            ->name('Process Reverse Attributes Batch')
            ->onQueue('reverse-attributes')
            ->dispatch();
    }
}

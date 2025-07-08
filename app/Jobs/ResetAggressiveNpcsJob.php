<?php

namespace App\Jobs;

use App\Enums\BaseNpcCategory;
use App\Models\BaseNpc;
use App\Models\DynamicModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetAggressiveNpcsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * This job resets the is_aggressive flag to false for all NPCs (not MOBs)
     * Using a single update query for efficiency
     */
    public function handle(): void
    {
        // Set the database connection
        DynamicModel::setGlobalConnection('retro');

        // Update all matching records with a single query
        BaseNpc::where('category', BaseNpcCategory::NPC)
            ->where('is_aggressive', true)
            ->update(['is_aggressive' => false]);
    }
}

<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RecordQueueHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public const string CACHE_KEY = 'queue:heartbeat:last_ran_at';

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Cache::store('redis')->forever(self::CACHE_KEY, now()->toIso8601String());
    }
}

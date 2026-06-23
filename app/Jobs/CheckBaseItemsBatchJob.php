<?php

namespace App\Jobs;

use App\Models\BaseItem;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class CheckBaseItemsBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    protected int $chunkSize = 300;

    public function handle()
    {
        foreach (app(WorldTemplateConnectionResolver::class)->visibleSlugs() as $world) {
            Cache::store('redis')->put("problem_base_items_{$world}_batch_status", json_encode([
                'started_at' => now()->toDateTimeString(),
                'status' => 'started',
                'processed_chunks' => 0,
                'chunks' => ceil(BaseItem::on($world)->count() / $this->chunkSize),
            ]), 3600);
            BaseItem::setGlobalConnection($world);
            $total = BaseItem::on($world)->count();
            $chunks = ceil($total / $this->chunkSize);
            $chunkJobs = [];
            for ($i = 0; $i < $chunks; $i++) {
                $chunkJobs[] = new CheckBaseItemsChunkJob($world, $i, $this->chunkSize);
            }
            Bus::batch($chunkJobs)->dispatch();
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\BaseItem;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class CheckBaseItemsBatchJob implements ShouldQueue
{
    use Dispatchable, Queueable, Batchable;

    protected array $worlds = ['retro', 'legacy'];
    protected int $chunkSize = 300;

    public function handle()
    {
        foreach ($this->worlds as $world) {
            BaseItem::setGlobalConnection($world);
            $total = BaseItem::on($world)->count();
            $chunks = ceil($total / $this->chunkSize);
            $chunkJobs = [];
            for ($i = 0; $i < $chunks; ++$i) {
                $chunkJobs[] = new CheckBaseItemsChunkJob($world, $i, $this->chunkSize);
            }
            Bus::batch($chunkJobs)
                ->then(function (Batch $batch) use ($world) {
                    $results = [];
                    for ($i = 0; $i < count($batch->jobs); ++$i) {
                        $partial = Cache::store('redis')->get("problem_base_items_{$world}_chunk_{$i}");
                        $data = json_decode($partial ?? '[]', true);
                        $results = array_merge($results, $data);
                        Cache::store('redis')->forget("problem_base_items_{$world}_chunk_{$i}");
                    }
                    Cache::store('redis')->put("problem_base_items_{$world}", json_encode($results), 3600);
                })
                ->dispatch();
        }
    }
}

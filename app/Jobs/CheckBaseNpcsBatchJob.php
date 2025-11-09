<?php

namespace App\Jobs;

use App\Models\BaseNpc;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class CheckBaseNpcsBatchJob implements ShouldQueue
{
    use Dispatchable, Queueable, Batchable;

    protected array $worlds = ['retro', 'legacy'];
    protected int $chunkSize = 300;

    public function handle()
    {
        foreach ($this->worlds as $world) {
            Cache::store('redis')->put("problem_base_npcs_{$world}_batch_status", json_encode([
                'started_at' => now()->toDateTimeString(),
                'status' => 'started',
                'processed_chunks' => 0,
            ]), 3600);
            BaseNpc::setGlobalConnection($world);
            $total = BaseNpc::on($world)->count();
            $chunks = ceil($total / $this->chunkSize);
            $chunkJobs = [];
            for ($i = 0; $i < $chunks; ++$i) {
                $chunkJobs[] = new CheckBaseNpcsChunkJob($world, $i, $this->chunkSize);
            }
            Bus::batch($chunkJobs)
                ->progress(function ($batch) use ($world) {
                    $status = Cache::store('redis')->get("problem_base_npcs_{$world}_batch_status");
                    $decoded = json_decode($status ?? '{}', true);
                    $decoded['processed_chunks'] = $batch->processed();
                    $decoded['updated_at'] = now()->toDateTimeString();
                    Cache::store('redis')->put("problem_base_npcs_{$world}_batch_status", json_encode($decoded), 3600);
                })
                ->then(function ($batch) use ($world) {
                    $results = [];
                    for ($i = 0; $i < count($batch->jobs); ++$i) {
                        $partial = Cache::store('redis')->get("problem_base_npcs_{$world}_chunk_{$i}");
                        $data = json_decode($partial ?? '[]', true);
                        $results = array_merge($results, $data);
                        Cache::store('redis')->forget("problem_base_npcs_{$world}_chunk_{$i}");
                    }
                    Cache::store('redis')->put("problem_base_npcs_{$world}", json_encode($results), 3600);
                    Cache::store('redis')->put("problem_base_npcs_{$world}_batch_status", json_encode([
                        'finished_at' => now()->toDateTimeString(),
                        'status' => 'finished',
                        'processed_chunks' => count($batch->jobs)
                    ]), 3600);
                })
                ->dispatch();
        }
    }
}

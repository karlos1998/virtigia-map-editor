<?php

namespace App\Jobs;

use App\Models\BaseItem;
use App\Services\BaseItemUsageViewService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class RefreshBaseItemUsageViewBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $world,
        private readonly int $chunkSize = 500,
    ) {}

    public function handle(BaseItemUsageViewService $baseItemUsageViewService): void
    {
        $total = BaseItem::on($this->world)->count();
        $chunks = (int) ceil($total / $this->chunkSize);

        if ($total === 0) {
            $baseItemUsageViewService->clear($this->world);

            return;
        }

        $dialogItemIdsCacheKey = $baseItemUsageViewService->cacheDialogItemIds($this->world);
        $statusKey = "base_item_usage_view_{$this->world}_batch_status";

        Cache::store('redis')->put($statusKey, json_encode([
            'world' => $this->world,
            'started_at' => now()->toDateTimeString(),
            'status' => 'started',
            'processed_chunks' => 0,
            'chunks' => $chunks,
            'chunk_size' => $this->chunkSize,
        ]), 7200);

        $chunkJobs = [];

        for ($chunkIndex = 0; $chunkIndex < $chunks; $chunkIndex++) {
            $chunkJobs[] = new RefreshBaseItemUsageViewChunkJob(
                $this->world,
                $chunkIndex,
                $this->chunkSize,
                $dialogItemIdsCacheKey
            );
        }

        Bus::batch($chunkJobs)
            ->name("Refresh Base Item Usage View {$this->world}")
            ->finally(function () use ($dialogItemIdsCacheKey, $statusKey): void {
                Cache::store('redis')->forget($dialogItemIdsCacheKey);

                $status = json_decode(Cache::store('redis')->get($statusKey) ?? '{}', true);
                $status['status'] = 'finished';
                $status['finished_at'] = now()->toDateTimeString();
                Cache::store('redis')->put($statusKey, json_encode($status), 7200);
            })
            ->dispatch();
    }
}

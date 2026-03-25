<?php

namespace App\Jobs;

use App\Services\BaseItemUsageViewService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshBaseItemUsageViewChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $world,
        private readonly int $chunkIndex,
        private readonly int $chunkSize,
        private readonly string $dialogItemIdsCacheKey,
    ) {}

    public function handle(BaseItemUsageViewService $baseItemUsageViewService): void
    {
        $dialogItemIds = Cache::store('redis')->get($this->dialogItemIdsCacheKey, []);

        $baseItemUsageViewService->refreshChunk(
            $this->world,
            $this->chunkIndex,
            $this->chunkSize,
            is_array($dialogItemIds) ? $dialogItemIds : []
        );

        $statusKey = "base_item_usage_view_{$this->world}_batch_status";
        $status = json_decode(Cache::store('redis')->get($statusKey) ?? '{}', true);
        $status['processed_chunks'] = ($status['processed_chunks'] ?? 0) + 1;
        $status['updated_at'] = now()->toDateTimeString();
        Cache::store('redis')->put($statusKey, json_encode($status), 7200);
    }
}

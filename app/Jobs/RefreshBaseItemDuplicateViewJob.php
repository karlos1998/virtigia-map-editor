<?php

namespace App\Jobs;

use App\Models\DynamicModel;
use App\Services\BaseItemDuplicateViewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RefreshBaseItemDuplicateViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(private readonly string $world) {}

    public function handle(BaseItemDuplicateViewService $baseItemDuplicateViewService): void
    {
        $statusKey = "base_item_duplicate_view_{$this->world}_batch_status";

        DynamicModel::setGlobalConnection($this->world);

        Cache::store('redis')->put($statusKey, json_encode([
            'world' => $this->world,
            'started_at' => now()->toDateTimeString(),
            'status' => 'started',
            'processed_chunks' => 0,
            'chunks' => 1,
            'rows' => 0,
        ]), 7200);

        try {
            $rows = $baseItemDuplicateViewService->refresh($this->world);

            $status = json_decode(Cache::store('redis')->get($statusKey) ?? '{}', true);
            $status['status'] = 'finished';
            $status['processed_chunks'] = 1;
            $status['rows'] = $rows;
            $status['updated_at'] = now()->toDateTimeString();
            $status['finished_at'] = now()->toDateTimeString();

            Cache::store('redis')->put($statusKey, json_encode($status), 7200);
        } catch (Throwable $throwable) {
            $status = json_decode(Cache::store('redis')->get($statusKey) ?? '{}', true);
            $status['status'] = 'failed';
            $status['updated_at'] = now()->toDateTimeString();
            $status['failed_at'] = now()->toDateTimeString();
            $status['error'] = $throwable->getMessage();

            Cache::store('redis')->put($statusKey, json_encode($status), 7200);

            throw $throwable;
        } finally {
            DynamicModel::clearGlobalConnection();
        }
    }
}

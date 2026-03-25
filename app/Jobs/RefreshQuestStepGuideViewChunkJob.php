<?php

namespace App\Jobs;

use App\Services\QuestStepGuideViewService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class RefreshQuestStepGuideViewChunkJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $world,
        private readonly int $chunkIndex,
        private readonly int $chunkSize,
    ) {}

    public function handle(QuestStepGuideViewService $questStepGuideViewService): void
    {
        $questStepGuideViewService->refreshChunk(
            $this->world,
            $this->chunkIndex,
            $this->chunkSize,
        );

        $statusKey = "quest_step_guide_view_{$this->world}_batch_status";
        $status = json_decode(Cache::store('redis')->get($statusKey) ?? '{}', true);
        $status['processed_chunks'] = ($status['processed_chunks'] ?? 0) + 1;
        $status['updated_at'] = now()->toDateTimeString();
        Cache::store('redis')->put($statusKey, json_encode($status), 7200);
    }
}

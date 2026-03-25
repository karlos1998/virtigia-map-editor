<?php

namespace App\Jobs;

use App\Models\QuestStep;
use App\Services\QuestStepGuideViewService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

class RefreshQuestStepGuideViewBatchJob implements ShouldQueue
{
    use Batchable, Dispatchable, Queueable;

    public int $timeout = 3600;

    public function __construct(
        private readonly string $world,
        private readonly int $chunkSize = 20,
    ) {}

    public function handle(QuestStepGuideViewService $questStepGuideViewService): void
    {
        $total = QuestStep::on($this->world)->count();
        $chunks = (int) ceil($total / $this->chunkSize);

        if ($total === 0) {
            $questStepGuideViewService->clear($this->world);

            return;
        }

        $statusKey = "quest_step_guide_view_{$this->world}_batch_status";

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
            $chunkJobs[] = new RefreshQuestStepGuideViewChunkJob(
                $this->world,
                $chunkIndex,
                $this->chunkSize,
            );
        }

        Bus::batch($chunkJobs)
            ->name("Refresh Quest Step Guide View {$this->world}")
            ->finally(function () use ($statusKey): void {
                $status = json_decode(Cache::store('redis')->get($statusKey) ?? '{}', true);
                $status['status'] = 'finished';
                $status['finished_at'] = now()->toDateTimeString();
                Cache::store('redis')->put($statusKey, json_encode($status), 7200);
            })
            ->dispatch();
    }
}

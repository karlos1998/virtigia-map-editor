<?php

namespace App\Jobs;

use App\Models\WorldTemplateUpdate;
use App\Services\WorldTemplateSnapshotService;
use App\Services\WorldTemplateUpdateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessWorldTemplateUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $updateId, public readonly bool $apply = false)
    {
        $this->onConnection('world-template-updates');
        $this->onQueue('world-template-updates');
    }

    public function handle(WorldTemplateSnapshotService $snapshots): void
    {
        $expected = $this->apply ? 'queued_apply' : 'queued';
        $claimed = WorldTemplateUpdate::query()->whereKey($this->updateId)->where('status', $expected)
            ->update(['status' => $this->apply ? 'applying' : 'comparing', 'message' => $this->apply ? 'Weryfikacja i zapis produkcji…' : 'Przygotowuję migawkę i porównuję…']);
        if ($claimed !== 1) {
            return;
        }

        $update = WorldTemplateUpdate::query()->findOrFail($this->updateId);
        if ($this->apply) {
            $snapshots->apply($update);
        } else {
            $snapshots->prepare($update);
        }
    }

    public function failed(Throwable $error): void
    {
        app(WorldTemplateUpdateService::class)->failed($this->updateId, $error);
    }
}

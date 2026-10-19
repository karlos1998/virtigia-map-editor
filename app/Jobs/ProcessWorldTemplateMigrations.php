<?php

namespace App\Jobs;

use App\Models\WorldTemplateUpdate;
use App\Services\WorldTemplateMigrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessWorldTemplateMigrations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public readonly string $statusConnection;

    public function __construct(public readonly int $updateId)
    {
        $this->statusConnection = DB::getDefaultConnection();
        $this->onConnection('world-template-updates');
        $this->onQueue('world-template-updates');
    }

    public function handle(WorldTemplateMigrationService $migrations): void
    {
        $claimed = WorldTemplateUpdate::on($this->statusConnection)->whereKey($this->updateId)->where('status', 'queued_migration')
            ->update(['status' => 'migrating', 'message' => 'Sprawdzam brakujące migracje w obu bazach…']);
        if ($claimed !== 1) {
            return;
        }
        $migrations->run(WorldTemplateUpdate::on($this->statusConnection)->findOrFail($this->updateId));
    }

    public function failed(Throwable $error): void
    {
        $update = WorldTemplateUpdate::on($this->statusConnection)->find($this->updateId);
        if ($update !== null && in_array($update->status, ['queued_migration', 'migrating'], true)) {
            app(WorldTemplateMigrationService::class)->markFailed($update, $error);
        }
    }
}

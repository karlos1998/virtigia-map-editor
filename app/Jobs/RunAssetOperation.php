<?php

namespace App\Jobs;

use App\Services\AssetOperationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunAssetOperation implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public bool $failOnTimeout = true;

    public function __construct(public readonly string $operationId)
    {
        $this->onConnection('asset-manager');
        $this->onQueue('asset-manager');
    }

    public function handle(AssetOperationService $operations): void
    {
        $operations->run($this->operationId);
    }

    public function failed(Throwable $exception): void
    {
        app(AssetOperationService::class)->fail($this->operationId, $exception);
    }
}

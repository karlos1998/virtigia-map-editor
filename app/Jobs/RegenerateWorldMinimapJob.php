<?php

namespace App\Jobs;

use App\Models\DynamicModel;
use App\Services\WorldMinimapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegenerateWorldMinimapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        private readonly string $world
    ) {
    }

    public function handle(WorldMinimapService $worldMinimapService): void
    {
        DynamicModel::setGlobalConnection($this->world);
        $worldMinimapService->regenerate();
    }
}

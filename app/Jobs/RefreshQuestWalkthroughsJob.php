<?php

namespace App\Jobs;

use App\Services\QuestWalkthrough\QuestWalkthroughService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Checks a world template for quest-related changes and regenerates the affected walkthroughs.
 */
class RefreshQuestWalkthroughsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $world,
        public readonly bool $force = false,
    ) {}

    public function uniqueId(): string
    {
        return $this->world;
    }

    public function handle(QuestWalkthroughService $service): void
    {
        $service->refreshWorld($this->world, $this->force);
    }
}

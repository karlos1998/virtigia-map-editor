<?php

namespace App\Jobs;

use App\Services\DialogQuestAvailabilityService;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class RefreshDialogQuestAvailabilityJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 3600;

    public function __construct(public readonly string $world) {}

    public function uniqueId(): string
    {
        return $this->world;
    }

    public function handle(DialogQuestAvailabilityService $availability, WorldTemplateConnectionResolver $resolver): void
    {
        $connection = $resolver->connectionNameFor($this->world);
        if ($connection !== null) {
            $availability->refresh(DB::connection($connection));
        }
    }
}

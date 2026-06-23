<?php

use App\Jobs\CheckBaseItemsBatchJob;
use App\Jobs\CheckBaseNpcsBatchJob;
use App\Jobs\CombineNpcsIntoGroupsJob;
use App\Jobs\DispatchFindNearestRespForMaps;
use App\Jobs\FillMissingTeleportMapNamesForWorldJob;
use App\Jobs\RecordQueueHeartbeatJob;
use App\Jobs\RefreshBaseItemDuplicateViewJob;
use App\Jobs\RefreshBaseItemUsageViewBatchJob;
use App\Jobs\RefreshQuestStepGuideViewBatchJob;
use App\Jobs\ResetAggressiveNpcsJob;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CombineNpcsIntoGroupsJob)->hourly();

Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::command('telescope:prune --hours=48')->daily();

Schedule::command('queue:prune-failed --hours=6')->hourly();

Schedule::job(new RecordQueueHeartbeatJob)->everyTenMinutes();

Schedule::job(new DispatchFindNearestRespForMaps)->everyFourHours();

Schedule::job(new ResetAggressiveNpcsJob)->hourly();

Schedule::job(new CheckBaseItemsBatchJob)->hourly();
Schedule::job(new CheckBaseNpcsBatchJob)->hourly();
foreach (app(WorldTemplateConnectionResolver::class)->visibleSlugs() as $world) {
    Schedule::job(new FillMissingTeleportMapNamesForWorldJob($world))->hourly();
    Schedule::job(new RefreshBaseItemUsageViewBatchJob($world))->hourly();
    Schedule::job(new RefreshBaseItemDuplicateViewJob($world))->everyThirtyMinutes();
    Schedule::job(new RefreshQuestStepGuideViewBatchJob($world))->hourly();
}

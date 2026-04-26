<?php

use App\Jobs\CheckBaseItemsBatchJob;
use App\Jobs\CheckBaseNpcsBatchJob;
use App\Jobs\CombineNpcsIntoGroupsJob;
use App\Jobs\DispatchFindNearestRespForMaps;
use App\Jobs\FillMissingTeleportMapNamesForWorldJob;
use App\Jobs\RefreshBaseItemUsageViewBatchJob;
use App\Jobs\RefreshQuestStepGuideViewBatchJob;
use App\Jobs\ResetAggressiveNpcsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CombineNpcsIntoGroupsJob)->hourly();

Schedule::command('horizon:snapshot')->everyFiveMinutes();

Schedule::command('telescope:prune --hours=48')->daily();

Schedule::job(new DispatchFindNearestRespForMaps)->everyFourHours();

Schedule::job(new ResetAggressiveNpcsJob)->hourly();

Schedule::job(new CheckBaseItemsBatchJob)->hourly();
Schedule::job(new CheckBaseNpcsBatchJob)->hourly();
Schedule::job(new FillMissingTeleportMapNamesForWorldJob('retro'))->hourly();
Schedule::job(new FillMissingTeleportMapNamesForWorldJob('legacy'))->hourly();
Schedule::job(new RefreshBaseItemUsageViewBatchJob('retro'))->hourly();
Schedule::job(new RefreshBaseItemUsageViewBatchJob('legacy'))->hourly();
Schedule::job(new RefreshQuestStepGuideViewBatchJob('retro'))->hourly();
Schedule::job(new RefreshQuestStepGuideViewBatchJob('legacy'))->hourly();

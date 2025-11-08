<?php

use App\Jobs\CombineNpcsIntoGroupsJob;
use App\Jobs\DispatchFindNearestRespForMaps;
use App\Jobs\ResetAggressiveNpcsJob;
use App\Jobs\CheckBaseItemsJob;
use App\Jobs\CheckBaseNpcsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CombineNpcsIntoGroupsJob)->hourly();

Schedule::command('telescope:prune --hours=48')->daily();

Schedule::job(new DispatchFindNearestRespForMaps)->everyFourHours();

Schedule::job(new ResetAggressiveNpcsJob)->hourly();

Schedule::job(new CheckBaseItemsJob)->hourly();
Schedule::job(new CheckBaseNpcsJob)->hourly();

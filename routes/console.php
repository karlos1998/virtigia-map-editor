<?php

use App\Jobs\CombineNpcsIntoGroupsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::job(new CombineNpcsIntoGroupsJob)->hourly();

Schedule::command('telescope:prune --hours=48')->daily();

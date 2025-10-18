<?php

namespace App\Jobs;

use App\Models\DynamicModel;
use App\Models\Map;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchFindNearestRespForMaps implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $worlds = ['retro', 'legacy'];

        foreach ($worlds as $world) {
            DynamicModel::setGlobalConnection($world);

            $maps = Map::whereNull('respawn_point_id')->get();

            foreach ($maps as $map) {
                dispatch(new FindNearestRespForMap($map, $world));
            }
        }
    }
}

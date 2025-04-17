<?php

namespace App\Jobs;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Models\Npc;
use App\Models\NpcGroup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CombineNpcsIntoGroupsJob implements ShouldQueue
{
    use Queueable;

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
        DynamicModel::setGlobalConnection('retro');

//        Npc::whereHas('base', function ($query){
//            $query
//                ->where('lvl', '>=', 13)
//                ->whereIn('rank', [BaseNpcRank::NORMAL, BaseNpcRank::ELITE, BaseNpcRank::ELITE_II]);
//        })->whereHas('locations')->chunk(100, function($npcsChunk){
//            foreach ($npcsChunk as $npc) {
//                $location = $npc->locations->first();
//
//            }
//        });

        Map::chunk(100, function($mapsChunk) {
            foreach ($mapsChunk as $map) {
                $mapNpcs = $map->npcs()->whereNull('group_id')->whereHas('base', function ($query) {
                    $query->where('lvl', '>=', 13)
                        ->where('category', BaseNpcCategory::MOB)
                        ->whereIn('rank', [BaseNpcRank::NORMAL, BaseNpcRank::ELITE, BaseNpcRank::ELITE_II]);
                })->get();

                while ($mapNpcs->isNotEmpty()) {
                    $currentNpc = $mapNpcs->shift();
                    $group = collect();
                    $queue = collect();
                    $group->push($currentNpc);
                    $queue->push($currentNpc);
                    while ($queue->isNotEmpty() && $group->count() < 5) {
                        $current = $queue->shift();
                        [$nearby, $mapNpcs] = $mapNpcs->partition(function ($npc) use ($current, $map) {
                            $dx = $npc->pivot->x - $current->pivot->x;
                            $dy = $npc->pivot->y - $current->pivot->y;
                            if (abs($dx) > 2 || abs($dy) > 2) {
                                return false;
                            }
                            if (abs($dx) <= 1 && abs($dy) <= 1) {
                                return true;
                            }
                            if (abs($dx) === 2 && $dy === 0) {
                                $ix = min($npc->pivot->x, $current->pivot->x) + 1;
                                $iy = $current->pivot->y;
                                return $map->col[$iy * $map->x + $ix] === '0';
                            }
                            if (abs($dy) === 2 && $dx === 0) {
                                $ix = $current->pivot->x;
                                $iy = min($npc->pivot->y, $current->pivot->y) + 1;
                                return $map->col[$iy * $map->x + $ix] === '0';
                            }
                            if (abs($dx) === 2 && abs($dy) === 2) {
                                $ix1 = $current->pivot->x;
                                $iy1 = $current->pivot->y + ($dy > 0 ? 1 : -1);
                                $ix2 = $current->pivot->x + ($dx > 0 ? 1 : -1);
                                $iy2 = $current->pivot->y;
                                return !($map->col[$iy1 * $map->x + $ix1] === '1' && $map->col[$iy2 * $map->x + $ix2] === '1');
                            }
                            return true;
                        });
                        foreach ($nearby as $npc) {
                            if ($group->count() < 5) {
                                $group->push($npc);
                                $queue->push($npc);
                            }
                        }
                    }
                    if ($group->count() > 1) {
//                        dd($group->map(fn($npc) => $npc->locations->first()->toArray()));
                        $groupModel = NpcGroup::create();
                        Npc::whereIn('id', $group->pluck('id'))->update(['group_id' => $groupModel->id]);
                    }
                }
//                dd();
            }
        });
    }
}

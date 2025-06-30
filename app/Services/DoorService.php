<?php

namespace App\Services;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Http\Resources\DoorResource;
use App\Http\Resources\TitanDoorResource;
use App\Models\Door;
use App\Models\BaseNpc;
use App\Models\Npc;
use App\Models\NpcLocation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

final class DoorService extends BaseService
{
    public function __construct(private readonly Door $doorModel)
    {
    }

    public function getTitanDoors()
    {
        $mapsWithTitanNpcs = NpcLocation::whereHas('npc.base', function ($query) {
            $query->where('rank', BaseNpcRank::TITAN)
                  ->where('category', BaseNpcCategory::MOB);
        })
        ->distinct()
        ->pluck('map_id');

        return $this->fetchData(
            TitanDoorResource::class,
            $this->doorModel
                ->whereIn('go_map_id', $mapsWithTitanNpcs)
                ->with(['map', 'targetMap', 'requiredBaseItem'])
        );

    }

    public function store(array $validated)
    {
        $this->doorModel->create($validated);
    }

    public function destroy(Door $door)
    {
        $door->delete();
    }

    public function move(Door $door, mixed $validated)
    {
        $reverseDoor = $this->doorModel->where('map_id', $door->go_map_id)->where('x', $door->go_x)->where('y', $door->go_y)->first();

        $door->x = $validated['x'];
        $door->y = $validated['y'];
        $door->save();

        if($reverseDoor) {
            $reverseDoor->go_x = $validated['x'];
            $reverseDoor->go_y = $validated['y'];
            $reverseDoor->save();
        }
    }

    public function updateLevel(Door $door, array $validated)
    {
        $door->min_lvl = $validated['min_lvl'];
        $door->max_lvl = $validated['max_lvl'];
        $door->save();
    }

    public function updateRequiredItem(Door $door, array $validated)
    {
        $door->requiredBaseItem()->associate($validated['base_item_id']);
        $door->save();
    }

    public function updateLevelRestrictions(array $validated)
    {
        $doors = $this->doorModel->whereIn('id', $validated['door_ids'])->with(['targetMap', 'map'])->get();
        $updatedTitans = [];

        foreach ($doors as $door) {
            // Find the titan NPC in the target map
            $titanNpc = Npc::whereHas('base', function ($query) {
                $query->where('rank', BaseNpcRank::TITAN)
                      ->where('category', BaseNpcCategory::MOB);
            })
            ->whereHas('locations', function ($query) use ($door) {
                $query->where('map_id', $door->go_map_id);
            })
            ->with('base')
            ->first();

            if ($titanNpc) {
                $titanLevel = $titanNpc->base->lvl;
                $oldMinLvl = $door->min_lvl;
                $oldMaxLvl = $door->max_lvl;

                // Update door level restrictions based on titan's level
                $door->min_lvl = max(1, $titanLevel - $validated['min_diff']);
                $door->max_lvl = $titanLevel + $validated['max_diff'];
                $door->save();

                // Add titan to the list of updated titans if not already added
                if (!isset($updatedTitans[$titanNpc->id])) {
                    $updatedTitans[$titanNpc->id] = [
                        'name' => $titanNpc->base->name,
                        'level' => $titanNpc->base->lvl,
                        'map' => $door->targetMap->name,
                        'doors' => []
                    ];
                }

                // Add door information to the titan's door list
                $updatedTitans[$titanNpc->id]['doors'][] = [
                    'id' => $door->id,
                    'source_map' => $door->map->name,
                    'coordinates' => "({$door->x}, {$door->y})",
                    'old_level_range' => $oldMinLvl && $oldMaxLvl ? "{$oldMinLvl} - {$oldMaxLvl}" : "None",
                    'new_level_range' => "{$door->min_lvl} - {$door->max_lvl}"
                ];

                // Log activity for each door update
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($door)
                    ->event('update-door-level-restrictions')
                    ->withProperties([
                        'titan' => [
                            'id' => $titanNpc->id,
                            'name' => $titanNpc->base->name,
                            'level' => $titanNpc->base->lvl
                        ],
                        'old_min_lvl' => $oldMinLvl,
                        'old_max_lvl' => $oldMaxLvl,
                        'new_min_lvl' => $door->min_lvl,
                        'new_max_lvl' => $door->max_lvl,
                        'min_diff' => $validated['min_diff'],
                        'max_diff' => $validated['max_diff']
                    ])
                    ->log("Updated level restrictions for door to titan {$titanNpc->base->name}");
            }
        }

        // Log a summary of all updates
        if (!empty($updatedTitans)) {
            activity()
                ->causedBy(Auth::user())
                ->event('bulk-update-door-level-restrictions')
                ->withProperties([
                    'titans' => array_values($updatedTitans),
                    'min_diff' => $validated['min_diff'],
                    'max_diff' => $validated['max_diff'],
                    'door_count' => count($doors)
                ])
                ->log("Bulk updated level restrictions for doors to " . count($updatedTitans) . " titans");
        }
    }
}

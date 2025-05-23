<?php

namespace App\Services;

use App\Models\Door;

final class DoorService
{
    public function __construct(private readonly Door $doorModel)
    {
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
}

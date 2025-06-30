<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveDoorRequest;
use App\Http\Requests\StoreDoorRequest;
use App\Http\Requests\UpdateDoorLevelRequest;
use App\Http\Requests\UpdateDoorLevelRestrictionsRequest;
use App\Http\Requests\UpdateDoorRequiredItemRequest;
use App\Models\Door;
use App\Services\DoorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DoorController extends Controller
{

    public function __construct(private readonly DoorService $doorService)
    {
    }

    public function titanDoors()
    {
        $titanDoors = $this->doorService->getTitanDoors();

        return Inertia::render('Door/TitanDoors', [
            'doors' => $titanDoors
        ]);
    }

    public function store(StoreDoorRequest $request)
    {
        $this->doorService->store($request->validated());
    }

    public function destroy(Door $door)
    {
        $this->doorService->destroy($door);
    }

    public function move(Door $door, MoveDoorRequest $request)
    {
        $this->doorService->move($door, $request->validated());
    }

    public function updateLevel(Door $door, UpdateDoorLevelRequest $request)
    {
        $this->doorService->updateLevel($door, $request->validated());
    }

    public function updateRequiredItem(Door $door, UpdateDoorRequiredItemRequest $request)
    {
        $this->doorService->updateRequiredItem($door, $request->validated());
    }

    public function updateLevelRestrictions(UpdateDoorLevelRestrictionsRequest $request)
    {
        $this->doorService->updateLevelRestrictions($request->validated());
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveDoorRequest;
use App\Http\Requests\StoreDoorRequest;
use App\Http\Requests\UpdateDoorLevelRequest;
use App\Models\Door;
use App\Services\DoorService;
use Illuminate\Http\Request;

class DoorController extends Controller
{

    public function __construct(private readonly DoorService $doorService)
    {
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
}

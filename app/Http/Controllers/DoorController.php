<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoorRequest;
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
}

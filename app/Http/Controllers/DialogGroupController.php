<?php

namespace App\Http\Controllers;

use App\Services\DialogGroupService;
use Illuminate\Http\JsonResponse;

class DialogGroupController extends Controller
{
    protected DialogGroupService $service;

    public function __construct(DialogGroupService $service)
    {
        $this->service = $service;
    }

    public function store(): JsonResponse
    {
        $group = $this->service->createGroup();
        return response()->json($group, 201);
    }

    public function show(int $groupId): JsonResponse
    {
        $group = $this->service->getGroup($groupId);
        return response()->json($group, 200);
    }

    public function destroy(int $groupId): JsonResponse
    {
        $this->service->deleteGroup($groupId);
        return response()->json(null, 204);
    }
}

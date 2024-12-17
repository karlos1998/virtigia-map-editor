<?php

namespace App\Http\Controllers;

use App\Http\Resources\DialogEdgeResource;
use App\Http\Resources\DialogNodeResource;
use App\Models\Dialog;
use App\Services\DialogService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class DialogController extends Controller
{
    public function __construct(private readonly DialogService $service)
    {
    }

    public function index()
    {

    }

    public function show(Dialog $dialog): \Inertia\Response
    {
        return Inertia::render('Dialog/Show', [
            'nodes' => DialogNodeResource::collection($dialog->nodes),
            'edges' => DialogEdgeResource::collection($dialog->edges),
        ]);
    }

//    public function store(): JsonResponse
//    {
//        $group = $this->service->createGroup();
//        return response()->json($group, 201);
//    }
//
//    public function show(int $groupId): JsonResponse
//    {
//        $group = $this->service->getGroup($groupId);
//        return response()->json($group, 200);
//    }
//
//    public function destroy(int $groupId): JsonResponse
//    {
//        $this->service->deleteGroup($groupId);
//        return response()->json(null, 204);
//    }
}

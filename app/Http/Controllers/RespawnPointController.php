<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRespawnPointRequest;
use App\Http\Requests\UpdateRespawnPointRequest;
use App\Http\Resources\RespawnPointResource;
use App\Models\RespawnPoint;
use App\Services\RespawnPointService;
use Inertia\Inertia;

class RespawnPointController extends Controller
{
    protected RespawnPointService $respawnPointService;

    public function __construct(RespawnPointService $respawnPointService)
    {
        $this->respawnPointService = $respawnPointService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $respawnPoints = RespawnPoint::withCount('maps')->get();
        return Inertia::render('RespawnPoint/Index', [
            'respawnPoints' => RespawnPointResource::collection($respawnPoints)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRespawnPointRequest $request)
    {
        $this->respawnPointService->create($request->validated());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRespawnPointRequest $request, RespawnPoint $respawnPoint)
    {
        $this->respawnPointService->update($respawnPoint, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RespawnPoint $respawnPoint)
    {
        $this->respawnPointService->delete($respawnPoint);
    }
}

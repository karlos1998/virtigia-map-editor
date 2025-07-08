<?php

namespace App\Http\Controllers;

use App\Enums\PvpType;
use App\Http\Requests\StoreMapRequest;
use App\Http\Requests\UpdateMapColsRequest;
use App\Http\Requests\UpdateMapImageRequest;
use App\Http\Requests\UpdateMapNameRequest;
use App\Http\Requests\UpdateMapPvpRequest;
use App\Http\Requests\UpdateMapRespawnPointRequest;
use App\Http\Requests\UpdateMapWaterRequest;
use App\Http\Resources\DoorResource;
use App\Http\Resources\MapResource;
use App\Http\Resources\NpcResource;
use App\Http\Resources\RespawnPointResource;
use App\Models\Map;
use App\Models\RespawnPoint;
use App\Services\MapService;
use App\Services\NpcService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MapController extends Controller
{
    public function __construct(
        private MapService $mapService,
        private NpcService $npcService
    )
    {
    }

    public function create()
    {
        return Inertia::render('Map/Create', [

        ]);
    }

    public function store(StoreMapRequest $request)
    {
        $map = $this->mapService->store($request->img, $request->fileName, $request->name);
        return to_route('maps.show', $map->id);
    }

    public function index()
    {
        return Inertia::render('Map/Index', [
            'maps' => $this->mapService->getAll(),
        ]);
    }

    public function show(Map $map)
    {
        // Eager load all necessary relationships to reduce database queries
        $map->load(['respawnPoint', 'npcs.base', 'doors.requiredBaseItem', 'doors.targetMap']);

        // Also eager load the map relationship for respawn points
        $respawnPoints = RespawnPoint::with('map')->get();

        // Get doors that lead to this map
        $doorsLeadingToMap = $this->mapService->getDoorsLeadingToMap($map);

        // Get dialog nodes that teleport to this map
        $dialogNodesTeleportingToMap = $this->mapService->getDialogNodesTeleportingToMap($map);

        // Get items that teleport to this map
        $itemsTeleportingToMap = $this->mapService->getItemsTeleportingToMap($map);

        return Inertia::render('Map/Show', [
            'map' => MapResource::make($map),
            'npcs' => NpcResource::collection($map->npcs),
            'doors' => DoorResource::collection($map->doors),
            'pvpTypeList' => PvpType::toDropdownList(),
            'respawnPoints' => RespawnPointResource::collection($respawnPoints),
            'doorsLeadingToMap' => DoorResource::collection($doorsLeadingToMap),
            'dialogNodesTeleportingToMap' => $dialogNodesTeleportingToMap,
            'itemsTeleportingToMap' => $itemsTeleportingToMap,
        ]);
    }

    public function search(Request $request)
    {
        return MapResource::collection($this->mapService->search($request->get('search', '')));
    }

    public function updateCol(Map $map, UpdateMapColsRequest $request): void
    {
        $this->mapService->updateCol($map, $request->post('col'));
    }

    public function updateName(Map $map, UpdateMapNameRequest $request): void
    {
        $this->mapService->updateName($map, $request->input('name'));
    }

    public function updatePvp(Map $map, UpdateMapPvpRequest $request): void
    {
        $this->mapService->updatePvp($map, $request->input('pvp'));
    }

    public function updateRespawnPoint(Map $map, UpdateMapRespawnPointRequest $request): void
    {
        $this->mapService->updateRespawnPoint($map, $request->input('respawn_point_id'));
    }

    public function updateWater(Map $map, UpdateMapWaterRequest $request): void
    {
        $this->mapService->updateWater($map, $request->input('water'));
    }

    /**
     * Update the map image
     *
     * @param Map $map
     * @param UpdateMapImageRequest $request
     * @return void
     */
    public function updateImage(Map $map, UpdateMapImageRequest $request): void
    {
        $this->mapService->updateImage($map, $request->input('img'), $request->input('fileName'));
    }

    /**
     * Remove the specified map from storage.
     *
     * @param Map $map
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Map $map): \Illuminate\Http\RedirectResponse
    {
        $this->mapService->destroy($map);
        return to_route('maps.index');
    }
}

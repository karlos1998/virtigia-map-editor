<?php

namespace App\Http\Controllers;

use App\Enums\PvpType;
use App\Http\Requests\StoreMapRequest;
use App\Http\Requests\UpdateMapBattleground2Request;
use App\Http\Requests\UpdateMapBattlegroundRequest;
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
use App\Http\Resources\RenewableMapItemResource;
use App\Models\Map;
use App\Models\Door;
use App\Models\RespawnPoint;
use App\Services\MapService;
use App\Services\NpcService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Display a world minimap with all maps and doors.
     *
     * @return \Inertia\Response
     */
    public function world()
    {
        $mapService = $this->mapService;
        $cached = Cache::remember('maps.world.connected_maps', 600, function () use ($mapService) {
            return $mapService->getAllConnectedMaps();
        });

        return Inertia::render('Map/WorldMinimap', [
            'maps' => $cached['maps'],
            'doors' => $cached['doors'],
        ]);
    }

    public function show(Map $map)
    {
        // Eager load all necessary relationships to reduce database queries
        $map->load([
            'respawnPoint', 'npcs.base', 'doors.requiredBaseItem', 'doors.targetMap',
            'renewableMapItems.baseItem',
        ]);

        $respawnPoints = RespawnPoint::with('map')->get();
        $dialogNodesTeleportingToMap = $this->mapService->getDialogNodesTeleportingToMap($map);
        $itemsTeleportingToMap = $this->mapService->getItemsTeleportingToMap($map);

        return Inertia::render('Map/Show', [
            'map' => MapResource::make($map),
            'npcs' => NpcResource::collection($map->npcs),
            'doors' => DoorResource::collection($map->doors),
            'renewableItems' => RenewableMapItemResource::collection($map->renewableMapItems),
            'pvpTypeList' => PvpType::toDropdownList(),
            'respawnPoints' => RespawnPointResource::collection($respawnPoints),
            'doorsLeadingToMap' => $map->doorsLeadingToMap,
            'dialogNodesTeleportingToMap' => $dialogNodesTeleportingToMap,
            'itemsTeleportingToMap' => $itemsTeleportingToMap,
        ]);
    }

    public function search(Request $request)
    {
        return MapResource::collection($this->mapService->search($request->get('search', '')));
    }

    /**
     * Get map data as JSON (for API calls)
     *
     * @param Map $map
     * @return MapResource
     */
    public function getMapData(Map $map): MapResource
    {
        return MapResource::make($map);
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
     * Clear all collisions on the map
     *
     * @param Map $map
     * @return void
     */
    public function clearCollisions(Map $map): void
    {
        $this->mapService->clearCollisions($map);
    }

    /**
     * Clear all water on the map
     *
     * @param Map $map
     * @return void
     */
    public function clearWater(Map $map): void
    {
        $this->mapService->updateWater($map, '');
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

    /**
     * Copy the specified map and redirect to the new map.
     *
     * @param Map $map
     * @return \Illuminate\Http\RedirectResponse
     */
    public function copy(Map $map): \Illuminate\Http\RedirectResponse
    {
        $newMap = $this->mapService->copy($map);
        return to_route('maps.show', $newMap->id);
    }

    public function updateBattleground(Map $map, UpdateMapBattlegroundRequest $request): void
    {
        $this->mapService->updateBattleground($map, $request->input('battleground'));
    }

    public function updateBattleground2(Map $map, UpdateMapBattleground2Request $request): void
    {
        $this->mapService->updateBattleground2($map, $request->input('battleground2'));
    }

    public function updateTeleportLocked(Map $map, Request $request): void
    {
        $this->mapService->updateTeleportLocked($map, (bool)$request->input('is_teleport_locked', false));
    }
}

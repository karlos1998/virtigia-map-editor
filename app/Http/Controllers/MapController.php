<?php

namespace App\Http\Controllers;

use App\Enums\PvpType;
use App\Http\Requests\StoreMapRequest;
use App\Http\Requests\UpdateMapColsRequest;
use App\Http\Requests\UpdateMapNameRequest;
use App\Http\Requests\UpdateMapPvpRequest;
use App\Http\Requests\UpdateMapRespawnPointRequest;
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
        return Inertia::render('Map/Show', [
            'map' => MapResource::make($map),
            'npcs' => NpcResource::collection($map->npcs),
            'doors' => DoorResource::collection($map->doors()->with('requiredBaseItem')->get()),
            'pvpTypeList' => PvpType::toDropdownList(),
            'respawnPoints' => RespawnPointResource::collection(RespawnPoint::all()),
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
}

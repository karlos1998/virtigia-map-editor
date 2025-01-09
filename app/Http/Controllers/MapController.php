<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapRequest;
use App\Http\Resources\DoorResource;
use App\Http\Resources\MapResource;
use App\Http\Resources\NpcResource;
use App\Models\Map;
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
            'doors' => DoorResource::collection($map->doors),
        ]);
    }

    public function search(Request $request)
    {
        $this->mapService->search($request->get('search', ''));
    }
}

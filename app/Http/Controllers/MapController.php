<?php

namespace App\Http\Controllers;

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
        ]);
    }

    public function search(Request $request)
    {
        $this->mapService->search($request->get('search', ''));
    }
}

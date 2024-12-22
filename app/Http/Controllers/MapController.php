<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Services\MapService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MapController extends Controller
{
    public function __construct(private MapService $mapService)
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
        dd($map);
        return Inertia::render('Map/Show', [

        ]);
    }

    public function search(Request $request)
    {
        $this->mapService->search($request->get('search', ''));
    }
}

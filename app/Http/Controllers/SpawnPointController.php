<?php

namespace App\Http\Controllers;

use App\Enums\Profession;
use App\Http\Requests\StoreSpawnPointRequest;
use App\Http\Requests\UpdateSpawnPointRequest;
use App\Http\Resources\MapResource;
use App\Http\Resources\SpawnPointResource;
use App\Models\Map;
use App\Models\SpawnPoint;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpawnPointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $spawnPoints = SpawnPoint::with('map')->get();

        return Inertia::render('SpawnPoint/Index', [
            'spawnPoints' => SpawnPointResource::collection($spawnPoints),
            'professions' => Profession::toDropdownList()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSpawnPointRequest $request)
    {
        $validated = $request->validated();

        $existingSpawnPoint = SpawnPoint::where('profession', $validated['profession'])->first();
        if ($existingSpawnPoint) {
            $existingSpawnPoint->update($validated);
        }

        SpawnPoint::create($validated);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSpawnPointRequest $request, SpawnPoint $spawnPoint)
    {
        $validated = $request->validated();

        $spawnPoint->update($validated);
        return redirect()->back();
    }

    /**
     * Set default spawn points for missing professions.
     */
    public function setDefaultForMissing(Request $request)
    {
        $defaultMap = Map::first();
        if (!$defaultMap) {
            return redirect()->back()->with('error', 'No maps found to set default spawn points.');
        }

        $existingProfessions = SpawnPoint::pluck('profession')->toArray();
        $missingProfessions = array_diff(Profession::valuesToList(), $existingProfessions);

        foreach ($missingProfessions as $profession) {
            SpawnPoint::create([
                'map_id' => $defaultMap->id,
                'x' => 1,
                'y' => 1,
                'profession' => $profession,
            ]);
        }

        return redirect()->back();
    }
}

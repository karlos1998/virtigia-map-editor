<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpawnPointResource;
use App\Models\SpawnPoint;
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
            'spawnPoints' => SpawnPointResource::collection($spawnPoints)
        ]);
    }
}

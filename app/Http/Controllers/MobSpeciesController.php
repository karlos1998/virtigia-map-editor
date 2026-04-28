<?php

namespace App\Http\Controllers;

use App\Http\Resources\MobSpeciesResource;
use App\Models\MobSpecies;
use Illuminate\Http\Request;

class MobSpeciesController extends Controller
{
    public function search(Request $request)
    {
        $query = trim((string) $request->get('query', ''));

        if ($query === '') {
            return response()->json([]);
        }

        $species = MobSpecies::query()
            ->where('name', 'like', '%'.$query.'%')
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json(MobSpeciesResource::collection($species));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $species = MobSpecies::query()->firstOrCreate([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'species' => MobSpeciesResource::make($species),
        ]);
    }
}


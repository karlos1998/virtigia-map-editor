<?php

namespace App\Http\Controllers;

use App\Http\Requests\AttachBaseNpcToMobSpeciesRequest;
use App\Http\Resources\MobSpeciesResource;
use App\Models\BaseNpc;
use App\Models\MobSpecies;
use App\Services\MobSpeciesService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MobSpeciesController extends Controller
{
    public function __construct(
        private readonly MobSpeciesService $mobSpeciesService,
    ) {}

    public function index()
    {
        return Inertia::render('MobSpecies/Index', [
            'mobSpecies' => $this->mobSpeciesService->getAll(),
        ]);
    }

    public function show(MobSpecies $mobSpecies)
    {
        return Inertia::render('MobSpecies/Show', [
            'mobSpecies' => MobSpeciesResource::make($mobSpecies->load(['baseNpcs'])),
        ]);
    }

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

        if ($request->expectsJson()) {
            return response()->json([
                'species' => MobSpeciesResource::make($species),
            ]);
        }

        return back();
    }

    public function attachBaseNpc(MobSpecies $mobSpecies, AttachBaseNpcToMobSpeciesRequest $request)
    {
        $this->mobSpeciesService->attachBaseNpc($mobSpecies, (int) $request->validated('base_npc_id'));

        return back();
    }

    public function detachBaseNpc(MobSpecies $mobSpecies, BaseNpc $baseNpc)
    {
        $this->mobSpeciesService->detachBaseNpc($mobSpecies, (int) $baseNpc->id);

        return back();
    }
}

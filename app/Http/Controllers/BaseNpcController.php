<?php

namespace App\Http\Controllers;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Enums\Profession;
use App\Http\Requests\AttachBaseNpcLootRequest;
use App\Http\Requests\StoreBaseNpcRequest;
use App\Http\Requests\StoreSimpleBaseNpcRequest;
use App\Http\Requests\UpdateBaseNpcImageRequest;
use App\Http\Requests\UpdateBaseNpcRequest;
use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\NpcLocationResource;
use App\Http\Resources\PureNpcWithOnlyLocationsResource;
use App\Models\BaseNpc;
use App\Services\BaseNpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Resources\ActivityLogResource;
use Spatie\Activitylog\Models\Activity;

class BaseNpcController extends Controller
{
    public function __construct(private readonly BaseNpcService $baseNpcService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('BaseNpc/Index', [
            'baseNpcs' => $this->baseNpcService->getAll(),
        ]);
    }

    public function indexJson()
    {
        return response()->json(
            $this->baseNpcService->getAll()->jsonSerialize(),
        );
    }

    /**
     * Show the form for creating a new resource with advanced options.
     */
    public function createAdvanced()
    {
        return Inertia::render('BaseNpc/Create', [
            'availableRanks' => BaseNpcRank::toDropdownList(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('BaseNpc/SimpleCreate', [
            'availableRanks' => BaseNpcRank::toDropdownList(),
            'availableCategories' => BaseNpcCategory::toDropdownList(),
        ]);
    }

    /**
     * Store a newly created resource in storage using the advanced form.
     */
    public function store(StoreBaseNpcRequest $request)
    {
        $model = $this->baseNpcService->store($request->validated());
        return to_route('base-npcs.show', $model->id);
    }

    /**
     * Store a newly created resource in storage with image upload.
     */
    public function storeSimple(StoreSimpleBaseNpcRequest $request)
    {
        $baseNpc = $this->baseNpcService->storeSimple($request->validated());

        return to_route('base-npcs.show', $baseNpc->id);
    }

    /**
     * Display the specified resource.
     * @throws \Exception
     */
    public function show(BaseNpc $baseNpc)
    {
        // Get activity logs for this base NPC
        $logs = Activity::where('subject_type', BaseNpc::class)
            ->where('subject_id', $baseNpc->id)
            ->orWhere(function($query) use ($baseNpc) {
                // Also get logs for loot changes related to this NPC
                $query->where('description', 'like', '%loot%')
                      ->where('properties->attributes->base_npc_id', $baseNpc->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('BaseNpc/Show', [
            'baseNpc' => BaseNpcResource::make($baseNpc->load('loots')),
            'locations' => $this->baseNpcService->getLocations($baseNpc),
            'logs' => ActivityLogResource::collection($logs),
            'similarBaseNpcs' => Inertia::lazy(fn () => $this->baseNpcService->findSimilarBaseNpcs($baseNpc)),

            'availableRanks' => BaseNpcRank::toDropdownList(),
            'availableCategories' => BaseNpcCategory::toDropdownList(),
            'availableProfessions' => Profession::toDropdownList(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BaseNpc $baseNpc)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBaseNpcRequest $request, BaseNpc $baseNpc)
    {
        $this->baseNpcService->update($baseNpc, $request->validated());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BaseNpc $baseNpc)
    {
        $this->baseNpcService->destroy($baseNpc);
        return to_route('base-npcs.index');
    }

    public function search(Request $request)
    {
        return response()->json($this->baseNpcService->search($request->get('query', '')));
    }

    public function searchHero(Request $request)
    {
        return response()->json($this->baseNpcService->searchHero($request->get('query', '')));
    }

    public function attachLoot(BaseNpc $baseNpc, AttachBaseNpcLootRequest $request)
    {
        $this->baseNpcService->attachLoot($baseNpc, $request->get('baseItemId'));
    }

    public function detachLoot(BaseNpc $baseNpc, int $loot)
    {
        $this->baseNpcService->detachLoot($baseNpc, $loot);
    }

    public function attachLootsFromBaseNpc(BaseNpc $baseNpc, Request $request)
    {
        $this->baseNpcService->attachLootsFromBaseNpc($baseNpc, $request->get('sourceBaseNpcId'));
    }

    public function updateImage(BaseNpc $baseNpc, UpdateBaseNpcImageRequest $request)
    {
        $this->baseNpcService->updateImageFromBase64($baseNpc, $request->string('image'), $request->string('name'), 'img/npc');
    }


    //metoda dosc tymczasowa...
    public function forumGenerator() {
        $npcs = BaseNpc::with('loots', 'locations.locations.map')->where('rank', request()->enum('rank', BaseNpcRank::class))->orderBy('lvl', 'asc')->get();
        return view('npcs-forum-generator', ['npcs' => $npcs]);
    }

    /**
     * Find similar base NPCs with the exact same name
     */
    public function findSimilarBaseNpcs(BaseNpc $baseNpc)
    {
        return response()->json([
            'similarBaseNpcs' => $this->baseNpcService->findSimilarBaseNpcs($baseNpc)
        ]);
    }

    /**
     * Transfer NPCs from one base NPC to another
     */
    public function transferNpcs(BaseNpc $sourceBaseNpc, Request $request)
    {
        $targetBaseNpc = BaseNpc::findOrFail($request->get('targetBaseNpcId'));
        $this->baseNpcService->transferNpcs($sourceBaseNpc, $targetBaseNpc);
    }

    /**
     * Convert a BaseNpc to a layer (type 4)
     */
    public function convertToLayer(BaseNpc $baseNpc)
    {
        $this->baseNpcService->update($baseNpc, ['type' => 4]);
        return back();
    }

    /**
     * Revert a BaseNpc from a layer to a regular NPC (type 0)
     */
    public function revertFromLayer(BaseNpc $baseNpc)
    {
        $this->baseNpcService->update($baseNpc, ['type' => 0]);
        return back();
    }
}

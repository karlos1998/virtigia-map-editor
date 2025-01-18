<?php

namespace App\Http\Controllers;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Enums\Profession;
use App\Http\Requests\AttachBaseNpcLootRequest;
use App\Http\Requests\StoreBaseNpcRequest;
use App\Http\Requests\UpdateBaseNpcRequest;
use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\NpcLocationResource;
use App\Http\Resources\PureNpcWithOnlyLocationsResource;
use App\Models\BaseNpc;
use App\Services\BaseNpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

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
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('BaseNpc/Create', [
            'availableRanks' => BaseNpcRank::toDropdownList(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaseNpcRequest $request)
    {
        $model = $this->baseNpcService->store($request->validated());
        return to_route('base-npcs.show', $model->id);

    }

    /**
     * Display the specified resource.
     * @throws \Exception
     */
    public function show(BaseNpc $baseNpc)
    {
        return Inertia::render('BaseNpc/Show', [
            'baseNpc' => BaseNpcResource::make($baseNpc->load('loots')),
            'locations' => $this->baseNpcService->getLocations($baseNpc),

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

    public function attachLoot(BaseNpc $baseNpc, AttachBaseNpcLootRequest $request)
    {
        $this->baseNpcService->attachLoot($baseNpc, $request->get('baseItemId'));
    }

    public function detachLoot(BaseNpc $baseNpc, int $loot)
    {
        $this->baseNpcService->detachLoot($baseNpc, $loot);
    }
}

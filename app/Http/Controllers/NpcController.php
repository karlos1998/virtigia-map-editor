<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddNpcToGroupRequest;
use App\Http\Requests\CreateNpcGroupRequest;
use App\Http\Requests\StoreNpcRequest;
use App\Http\Requests\UpdateNpcLocationRequest;
use App\Http\Requests\UpdateNpcRequest;
use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\NpcResource;
use App\Models\Npc;
use App\Models\NpcLocation;
use App\Services\NpcService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class NpcController extends Controller
{
    public function __construct(private readonly NpcService $npcService)
    {
    }

    public function index(): \Inertia\Response
    {
        return Inertia::render('Npc/Index', [
            'npcs' => $this->npcService->getAll(),
        ]);
    }

    public function show(Npc $npc): \Inertia\Response
    {
        return Inertia::render('Npc/Show', [
            'baseNpc' => BaseNpcResource::make($npc->base),
            'npc' => NpcResource::make($npc->load(['locations', 'dialog'])),
        ]);
    }

    public function store(StoreNpcRequest $request): void
    {
        $this->npcService->store($request->validated());
    }

    public function destroyLocation(Npc $npc, NpcLocation $npcLocation): void
    {
        $this->npcService->destroyLocation($npc, $npcLocation);
    }

    public function update(Npc $npc, UpdateNpcRequest $request): void
    {
        $this->npcService->update($npc, $request->validated());
    }

    /**
     * @throws ValidationException
     */
    public function updateLocation(Npc $npc, NpcLocation $npcLocation, UpdatENpcLocationRequest $request): void
    {
        $this->npcService->updateLocation($npc, $npcLocation, $request->validated());
    }

    public function detachGroup(Npc $npc): void
    {
        $this->npcService->detachGroup($npc);
    }

    public function addToGroup(AddNpcToGroupRequest $request): void
    {
        $validated = $request->validated();

        $sourceNpc = Npc::findOrFail($validated['source_npc_id']);
        $targetNpc = Npc::findOrFail($validated['target_npc_id']);

        $this->npcService->addToGroup($sourceNpc, $targetNpc);
    }

    public function createGroup(CreateNpcGroupRequest $request): void
    {
        $validated = $request->validated();

        $npcs = Npc::whereIn('id', $validated['npc_ids'])->get();

        $this->npcService->createGroup($npcs);
    }
}

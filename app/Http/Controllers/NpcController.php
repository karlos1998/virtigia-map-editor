<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNpcRequest;
use App\Http\Requests\UpdateNpcLocationRequest;
use App\Http\Requests\UpdateNpcRequest;
use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\NpcResource;
use App\Models\Npc;
use App\Models\NpcLocation;
use App\Services\NpcService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NpcController extends Controller
{
    public function __construct(private readonly NpcService $npcService)
    {
    }

    public function index()
    {
        return Inertia::render('Npc/Index', [
            'npcs' => $this->npcService->getAll(),
        ]);
    }

    public function show(Npc $npc)
    {
        return Inertia::render('Npc/Show', [
            'baseNpc' => BaseNpcResource::make($npc->base),
            'npc' => NpcResource::make($npc->load(['locations', 'dialog'])),
        ]);
    }

    public function store(StoreNpcRequest $request)
    {
        $this->npcService->store($request->validated());
    }

    public function destroy(Npc $npc)
    {
        $this->npcService->destroy($npc);
    }

    public function update(Npc $npc, UpdateNpcRequest $request): void
    {
        $this->npcService->update($npc, $request->validated());
    }

    public function updateLocation(Npc $npc, NpcLocation $npcLocation, UpdatENpcLocationRequest $request): void
    {
        $this->npcService->updateLocation($npc, $npcLocation, $request->validated());
    }
}

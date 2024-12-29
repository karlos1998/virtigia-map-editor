<?php

namespace App\Http\Controllers;

use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\NpcResource;
use App\Models\Npc;
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
}

<?php

namespace App\Http\Controllers;

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
}

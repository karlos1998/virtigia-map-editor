<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class ProblemAssetsController extends Controller
{
    public function index(Request $request)
    {
        $world = Auth::getSession()->get('world', 'retro');
        $items = Cache::store('redis')->get("problem_base_items_{$world}");
        $npcs = Cache::store('redis')->get("problem_base_npcs_{$world}");
        return Inertia::render('ProblemAssets/Index', [
            'items' => json_decode($items ?? '[]', true),
            'npcs' => json_decode($npcs ?? '[]', true),
            'world' => $world,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplySuggestedStackPriceRequest;
use App\Models\BaseItem;
use App\Services\BaseItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProblemAssetsController extends Controller
{
    public function index(Request $request, BaseItemService $baseItems): Response
    {
        $world = $request->session()->get('world', 'retro');
        $items = Cache::store('redis')->get("problem_base_items_{$world}");
        $npcs = Cache::store('redis')->get("problem_base_npcs_{$world}");
        $items_batch = Cache::store('redis')->get("problem_base_items_{$world}_batch_status");
        $npcs_batch = Cache::store('redis')->get("problem_base_npcs_{$world}_batch_status");

        return Inertia::render('ProblemAssets/Index', [
            'suspiciousStacks' => fn () => $baseItems->suspiciousStacks(),
            'suspiciousStackThreshold' => BaseItemService::SUSPICIOUS_STACK_VALUE,
            'items' => json_decode($items ?? '[]', true),
            'npcs' => json_decode($npcs ?? '[]', true),
            'world' => $world,
            'itemsBatch' => json_decode($items_batch ?? '{}', true),
            'npcsBatch' => json_decode($npcs_batch ?? '{}', true),
        ]);
    }

    public function applySuggestedPrice(ApplySuggestedStackPriceRequest $request, BaseItem $baseItem, BaseItemService $baseItems): RedirectResponse
    {
        if ($request->world() !== $request->session()->get('world')) {
            throw ValidationException::withMessages(['world' => 'Wybrany świat się zmienił. Sprawdź aktualny raport.']);
        }

        $baseItems->applySuggestedStackPrice($baseItem, $request->expectedPrice(), $request->expectedQuantity());

        return back();
    }
}

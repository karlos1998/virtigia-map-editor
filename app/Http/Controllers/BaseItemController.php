<?php

namespace App\Http\Controllers;

use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Services\BaseItemService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BaseItemController extends Controller
{


    public function __construct(private readonly BaseItemService $baseItemService)
    {
    }

    /**
     * @throws \Exception
     */
    public function index(): \Inertia\Response
    {
        return Inertia::render('BaseItem/Index', [
            'items' => $this->baseItemService->getAll(),
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(BaseItemResource::collection($this->baseItemService->search($request->get('query', ''))));
    }

    public function show(BaseItem $baseItem): \Inertia\Response
    {
        return Inertia::render('BaseItem/Show', [
            'baseItem' => BaseItemResource::make($baseItem),
        ]);
    }
}

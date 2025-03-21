<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateItemAttributesRequest;
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

    public function edit(BaseItem $baseItem): \Inertia\Response
    {
        return Inertia::render('BaseItem/Edit', [
            'baseItem' => BaseItemResource::make($baseItem),
        ]);
    }

    public function updateAttributes(BaseItem $baseItem, UpdateItemAttributesRequest $request)
    {
        $this->baseItemService->updateAttributes($baseItem, $request->input('attributes'));
        return to_route('base-items.show', $baseItem->id);
    }

    public function delete(BaseItem $baseItem)
    {
        $this->baseItemService->delete($baseItem);
        return to_route('base-items.index');
    }
}

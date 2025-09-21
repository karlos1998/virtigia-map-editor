<?php

namespace App\Http\Controllers;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Http\Requests\UpdateBaseItemImageRequest;
use App\Http\Requests\UpdateBaseItemRequest;
use App\Http\Requests\UpdateBaseNpcImageRequest;
use App\Http\Requests\UpdateItemAttributesRequest;
use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Services\BaseItemService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Resources\ActivityLogResource;
use Spatie\Activitylog\Models\Activity;

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
        $category = $request->string('category', null);
        return response()->json(BaseItemResource::collection(
            $this->baseItemService->search($request->string('query', ''), $request->collect('ids'), $category)
        ));
    }

    public function show(BaseItem $baseItem): \Inertia\Response
    {
        // Get activity logs for this base item
        $logs = Activity::where('subject_type', BaseItem::class)
            ->where('subject_id', $baseItem->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('BaseItem/Show', [
            'baseItem' => BaseItemResource::make($baseItem),
            'baseItemCategoryList' => BaseItemCategory::toDropdownList(),
            'baseItemRarityList' => BaseItemRarity::toDropdownList(),
            'baseItemCurrencyList' => BaseItemCurrency::toDropdownList(),
            'logs' => ActivityLogResource::collection($logs),
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
        $this->baseItemService->updateAttributes(
            $baseItem,
            $request->input('attributes'),
            $request->input('attribute_points', []),
            $request->input('manual_attribute_points', [])
        );
        return to_route('base-items.show', $baseItem->id);
    }

    public function delete(BaseItem $baseItem)
    {
        $this->baseItemService->delete($baseItem);
        return to_route('base-items.index');
    }

    public function updateImage(BaseItem $baseItem, UpdateBaseItemImageRequest $request)
    {
        $this->baseItemService->updateImageFromBase64($baseItem, $request->string('image'), $request->string('name'), 'img');
    }


    public function copy(BaseItem $baseItem)
    {
        $newBaseItem = $this->baseItemService->copy($baseItem);
        return to_route('base-items.show', $newBaseItem->id);
    }

    public function update(BaseItem $baseItem, UpdateBaseItemRequest $request)
    {
        $this->baseItemService->update($baseItem, $request->validated());
    }
}

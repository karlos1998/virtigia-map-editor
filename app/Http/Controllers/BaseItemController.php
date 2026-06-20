<?php

namespace App\Http\Controllers;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Enums\LegendaryBonus;
use App\Http\Requests\BulkAttachBaseItemsToBaseNpcLootRequest;
use App\Http\Requests\BulkUpdateBaseItemDescriptionRequest;
use App\Http\Requests\CreateBaseItemRequest;
use App\Http\Requests\IndexBaseItemRequest;
use App\Http\Requests\UpdateBaseItemImageRequest;
use App\Http\Requests\UpdateBaseItemRequest;
use App\Http\Requests\UpdateItemAttributesRequest;
use App\Http\Requests\UpdatePetImageRequest;
use App\Http\Resources\ActivityLogResource;
use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Services\BaseItemService;
use App\Services\BaseNpcService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class BaseItemController extends Controller
{
    public function __construct(
        private readonly BaseItemService $baseItemService,
        private readonly BaseNpcService $baseNpcService,
    ) {}

    /**
     * @throws \Exception
     */
    public function index(IndexBaseItemRequest $request): \Inertia\Response
    {
        $filters = $request->filters();

        return Inertia::render('BaseItem/Index', [
            'items' => $this->baseItemService->getAll($filters),
            'filters' => $filters,
            'legendaryBonusOptions' => LegendaryBonus::toDropdownList(
                fn (LegendaryBonus $bonus): array => ['bonus_value' => $bonus->bonusValue()],
            ),
        ]);
    }

    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        $category = $request->filled('category') ? $request->string('category')->toString() : null;
        $query = $request->string('query', '')->toString();
        $ids = $request
            ->collect('ids')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($request->boolean('paginated')) {
            $offset = max(0, $request->integer('offset', 0));
            $limit = max(1, min(100, $request->integer('limit', 30)));
            $results = $this->baseItemService->searchPaginated($query, $category, $offset, $limit);

            return response()->json([
                'data' => BaseItemResource::collection($results['items']),
                'meta' => [
                    'has_more' => $results['hasMore'],
                    'offset' => $offset,
                    'limit' => $limit,
                ],
            ]);
        }

        return response()->json(BaseItemResource::collection(
            $this->baseItemService->search($query, $ids, $category)
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
            $request->input('attribute_points'),
            $request->input('manual_attribute_points')
        );

        return to_route('base-items.show', $baseItem->id);
    }

    public function delete(BaseItem $baseItem): \Illuminate\Http\RedirectResponse
    {
        $this->baseItemService->delete($baseItem);

        return to_route('base-items.index');
    }

    public function updateImage(BaseItem $baseItem, UpdateBaseItemImageRequest $request): void
    {
        $this->baseItemService->updateImageFromBase64($baseItem, $request->string('image'), $request->string('name'), 'img');
    }

    public function updatePetImage(BaseItem $baseItem, UpdatePetImageRequest $request): void
    {
        $this->baseItemService->updatePetImageFromBase64($baseItem, $request->string('image'), $request->string('name'));
    }

    public function copy(BaseItem $baseItem): \Illuminate\Http\RedirectResponse
    {
        $newBaseItem = $this->baseItemService->copy($baseItem);

        return to_route('base-items.show', $newBaseItem->id);
    }

    public function update(BaseItem $baseItem, UpdateBaseItemRequest $request): void
    {
        $this->baseItemService->update($baseItem, $request->validated());
    }

    public function bulkUpdateDescription(BulkUpdateBaseItemDescriptionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $updatedCount = $this->baseItemService->bulkUpdateDescriptions(
            $validated['item_ids'],
            $validated['search_phrase'],
            $validated['replacement_phrase'] ?? '',
        );

        return back()->with('success', "Poprawiono opisy w {$updatedCount} przedmiotach.");
    }

    public function bulkAttachToBaseNpcLoots(BulkAttachBaseItemsToBaseNpcLootRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $baseNpc = BaseNpc::query()->findOrFail($validated['base_npc_id']);

        $result = $this->baseNpcService->attachLoots($baseNpc, $validated['item_ids']);

        return back()->with(
            'success',
            "Dodano {$result['attached_count']} przedmiotów jako loot. Pominięto {$result['skipped_count']} już przypisanych."
        );
    }

    public function create(): \Inertia\Response
    {
        return Inertia::render('BaseItem/Create', [
            'baseItemCategoryList' => BaseItemCategory::toDropdownList(),
            'baseItemRarityList' => BaseItemRarity::toDropdownList(),
            'baseItemCurrencyList' => BaseItemCurrency::toDropdownList(),
        ]);
    }

    public function store(CreateBaseItemRequest $request): \Illuminate\Http\RedirectResponse
    {
        $baseItem = $this->baseItemService->create($request->validated());

        return to_route('base-items.show', $baseItem->id);
    }
}

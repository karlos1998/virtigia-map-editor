<?php

namespace App\Services;

use App\Http\Resources\ShopResource;
use App\Models\BaseItem;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class ShopService extends BaseService
{
    public function __construct(private readonly Shop $shopModel) {}

    public function getAll()
    {
        return $this->fetchData(
            ShopResource::class,
            $this->shopModel->with('dialogs.npcs.locations'),
            new TableService(
                columns: [
                    'buy_price_percent' => new TableTextColumn(
                        placeholder: 'Cena skupu (%)',
                        sortable: true,
                    ),
                    'sell_price_percent' => new TableTextColumn(
                        placeholder: 'Cena sprzedaży (%)',
                        sortable: true,
                    ),
                    'max_buy_price' => new TableTextColumn(
                        placeholder: 'Maks. cena skupu',
                        sortable: true,
                    ),
                ],
                globalFilterColumns: ['name'],
            )
        );
    }

    public function addItem(Shop $shop, int $baseItemId, int $position)
    {
        $baseItem = BaseItem::findOrFail($baseItemId);
        $shop->items()->attach($baseItem, [
            'position' => $position,
        ]);

        $this->logItemAttachment($shop, $baseItem, $position);
    }

    /**
     * @param  array<int, int>  $baseItemIds
     * @return array{attached_count: int, skipped_count: int}
     */
    public function attachItems(Shop $shop, array $baseItemIds, int $startPosition): array
    {
        $uniqueBaseItemIds = collect($baseItemIds)
            ->map(fn (int|string $baseItemId): int => (int) $baseItemId)
            ->unique()
            ->values();
        $alreadyAttachedIds = $shop->items()
            ->whereIn('base_items.id', $uniqueBaseItemIds)
            ->pluck('base_items.id')
            ->unique()
            ->values();
        $baseItemsToAttach = BaseItem::query()
            ->whereIn('id', $uniqueBaseItemIds->diff($alreadyAttachedIds))
            ->get()
            ->sortBy(fn (BaseItem $baseItem): int => $uniqueBaseItemIds->search($baseItem->id))
            ->values();
        $occupiedPositions = $shop->items()
            ->pluck('shop_items.position')
            ->map(fn (int|string $position): int => (int) $position);
        $availablePositions = collect(range($startPosition, 79))
            ->diff($occupiedPositions)
            ->values();

        if ($availablePositions->count() < $baseItemsToAttach->count()) {
            throw ValidationException::withMessages([
                'start_position' => 'Od wybranej pozycji nie ma wystarczającej liczby wolnych miejsc w sklepie.',
            ]);
        }

        $shop->getConnection()->transaction(function () use ($availablePositions, $baseItemsToAttach, $shop): void {
            foreach ($baseItemsToAttach as $index => $baseItem) {
                $position = $availablePositions[$index];
                $shop->items()->attach($baseItem, ['position' => $position]);
                $this->logItemAttachment($shop, $baseItem, $position);
            }
        });

        return [
            'attached_count' => $baseItemsToAttach->count(),
            'skipped_count' => $alreadyAttachedIds->count(),
        ];
    }

    /**
     * @param  array<int, int>  $baseItemIds
     * @return array{detached_count: int, skipped_count: int}
     */
    public function detachItems(Shop $shop, array $baseItemIds): array
    {
        $uniqueBaseItemIds = collect($baseItemIds)
            ->map(fn (int|string $baseItemId): int => (int) $baseItemId)
            ->unique()
            ->values();
        $attachedItems = $shop->items()
            ->whereIn('base_items.id', $uniqueBaseItemIds)
            ->get()
            ->unique('id')
            ->values();

        $shop->getConnection()->transaction(function () use ($attachedItems, $shop): void {
            foreach ($attachedItems as $baseItem) {
                $position = (int) $baseItem->pivot->position;
                $shop->items()->detach($baseItem->id);
                $this->logItemDetachment($shop, $baseItem, $position);
            }
        });

        return [
            'detached_count' => $attachedItems->count(),
            'skipped_count' => $uniqueBaseItemIds->count() - $attachedItems->count(),
        ];
    }

    private function logItemAttachment(Shop $shop, BaseItem $baseItem, int $position): void
    {

        activity()
            ->causedBy(Auth::user())
            ->performedOn($shop)
            ->event('attach-item-to-shop')
            ->withProperty('base_item', $baseItem)
            ->withProperty('position', $position)
            ->log('attach-item-to-shop');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseItem)
            ->event('shop-item-attached')
            ->withProperty('shop', $shop)
            ->withProperty('position', $position)
            ->log('shop-item-attached');
    }

    public function deleteItem(Shop $shop, int $position)
    {
        $baseItem = $shop->items()->wherePivot('position', $position)->firstOrFail();

        $shop->items()->wherePivot('position', $position)->detach();

        $this->logItemDetachment($shop, $baseItem, $position);
    }

    private function logItemDetachment(Shop $shop, BaseItem $baseItem, int $position): void
    {
        activity()
            ->causedBy(Auth::user())
            ->performedOn($shop)
            ->event('detach-item-from-shop')
            ->withProperty('base_item', $baseItem)
            ->withProperty('position', $position)
            ->log('detach-item-from-shop');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($baseItem)
            ->event('shop-item-detach')
            ->withProperty('shop', $shop)
            ->withProperty('position', $position)
            ->log('shop-item-detached');
    }

    public function search(string $query = '')
    {
        return $this->shopModel->where('name', 'like', '%'.$query.'%')->limit(10)->get();
    }

    public function store(mixed $validated)
    {
        return $this->shopModel->create($validated);
    }

    public function update(Shop $shop, array $payload): void
    {
        $shop->update($payload);
    }
}

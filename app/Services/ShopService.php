<?php

namespace App\Services;

use App\Http\Resources\ShopResource;
use App\Models\BaseItem;
use App\Models\Shop;
use Illuminate\Support\Facades\Auth;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class ShopService extends BaseService
{

    public function __construct(private readonly Shop $shopModel)
    {
    }
    public function getAll()
    {
        return $this->fetchData(
            ShopResource::class,
            $this->shopModel->with('dialogs.npcs.locations'),
            new TableService(
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
        return $this->shopModel->where('name', 'like', '%' . $query . '%')->limit(10)->get();
    }

    public function store(mixed $validated)
    {
        return $this->shopModel->create($validated);
    }
}

<?php

namespace App\Services;

use App\Http\Resources\ShopResource;
use App\Models\Shop;
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
        $shop->items()->attach($baseItemId, [
            'position' => $position,
        ]);
    }

    public function deleteItem(Shop $shop, int $position)
    {
        $shop->items()->wherePivot('position', $position)->detach();
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

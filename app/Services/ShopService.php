<?php

namespace App\Services;

use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

final class ShopService extends BaseService
{

    public function __construct(private readonly Shop $shopModel)
    {
    }
    public function getAll()
    {
        return $this->fetchData(
            ShopResource::class,
            $this->shopModel->with('dialogs.npcs.locations')
        );
    }

    public function addItem(Shop $shop, int $baseItemId, int $position)
    {
        $shop->items()->attach($baseItemId, [
            'position' => $position,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\Shop;

final class ShopService
{

    public function __construct(private readonly Shop $shopModel)
    {
    }
    public function getAll()
    {
        return $this->shopModel->get();
    }

    public function addItem(Shop $shop, int $baseItemId, int $position)
    {
        $shop->items()->attach($baseItemId, [
            'position' => $position,
        ]);
    }
}

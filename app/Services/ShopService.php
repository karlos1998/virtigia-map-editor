<?php

namespace App\Services;

use App\Models\Shop;

final class ShopService
{
    public function getAll()
    {

    }

    public function addItem(Shop $shop, int $baseItemId, int $position)
    {
        $shop->items()->attach($baseItemId, [
            'position' => $position,
        ]);
    }
}

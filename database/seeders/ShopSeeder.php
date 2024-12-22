<?php

namespace Database\Seeders;

use App\Models\BaseItem;
use App\Models\Shop;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shop = Shop::create(['name' => 'test']);

        for($i = 0; $i < 10; $i++) {
            $shop->items()->attach([
                BaseItem::inRandomOrder()->first(),
            ], [
                'position' => $i,
            ]);
        }

    }
}

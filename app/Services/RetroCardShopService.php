<?php

namespace App\Services;

use App\Enums\BaseItemCategory;
use App\Models\BaseItem;
use App\Models\Shop;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class RetroCardShopService
{
    public const CAPACITY = 80;

    private const NAME_PREFIX = 'Zaciąg: Retro — karty #';

    /** @return Collection<int, Shop> */
    public function create(string $connection): Collection
    {
        return (new Shop)->setConnection($connection)->getConnection()->transaction(function () use ($connection): Collection {
            $cards = BaseItem::on($connection)->where('category', BaseItemCategory::PLAYING_CARDS)
                ->orderBy('id')->lockForUpdate()->get(['id', 'attributes'])
                ->filter(fn (BaseItem $item): bool => data_get($item->attributes, 'playingCard.deck') === 'retro');
            if ($cards->isEmpty()) {
                throw new InvalidArgumentException('Brak kart Retro. Najpierw uruchom card-game:import-retro.');
            }
            $shops = Shop::on($connection)->where('name', 'like', self::NAME_PREFIX.'%')->orderBy('id')->lockForUpdate()->get();
            $attached = $shops->flatMap(fn (Shop $shop): Collection => $shop->items()->pluck('base_items.id'));
            $missing = $cards->reject(fn (BaseItem $item): bool => $attached->contains($item->id))->values();
            foreach ($shops as $shop) {
                $positions = $shop->items()->pluck('shop_items.position')->all();
                $this->fill($shop, $missing, array_values(array_diff(range(0, self::CAPACITY - 1), $positions)));
            }
            $number = 1;
            while ($missing->isNotEmpty()) {
                while ($shops->contains('name', self::NAME_PREFIX.$number)) {
                    $number++;
                }
                $shop = Shop::on($connection)->create([
                    'name' => self::NAME_PREFIX.$number,
                    'binds_items_permanently' => false,
                    'buy_price_percent' => 0, 'sell_price_percent' => 100, 'max_buy_price' => 0,
                ]);
                $this->fill($shop, $missing, range(0, self::CAPACITY - 1));
                $shops->push($shop);
            }

            return $shops->loadCount('items');
        });
    }

    /**
     * @param  Collection<int, BaseItem>  $missing
     * @param  array<int, int>  $positions
     */
    private function fill(Shop $shop, Collection $missing, array $positions): void
    {
        foreach ($positions as $position) {
            if ($missing->isEmpty()) {
                break;
            }
            $shop->items()->attach($missing->shift()->id, ['position' => $position]);
        }
    }
}

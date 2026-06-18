<?php

namespace App\Services;

use App\Models\BaseItem;
use App\Models\BaseItemDuplicateView;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BaseItemDuplicateViewService
{
    /**
     * @return LengthAwarePaginator<int, BaseItemDuplicateView>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = BaseItemDuplicateView::query();

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search): void {
                $searchQuery->where('name', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $searchQuery
                        ->orWhere('duplicate_base_item_id', (int) $search)
                        ->orWhere('used_base_item_id', (int) $search);
                }
            });
        }

        if (($filters['category'] ?? null) !== null) {
            $query->where('category', $filters['category']);
        }

        if (($filters['rarity'] ?? null) !== null) {
            $query->where('rarity', $filters['rarity']);
        }

        return $query
            ->orderBy('name')
            ->orderBy('need_level')
            ->orderBy('duplicate_base_item_id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function refresh(string $connection): int
    {
        $baseItems = $this->getBaseItems($connection);
        $now = now();

        $rows = $this->buildRows($baseItems, $now);

        DB::connection($connection)->transaction(function () use ($connection, $rows): void {
            DB::connection($connection)->table('base_item_duplicate_views')->delete();

            foreach ($rows->chunk(500) as $chunk) {
                DB::connection($connection)
                    ->table('base_item_duplicate_views')
                    ->insert($chunk->all());
            }
        });

        return $rows->count();
    }

    /**
     * @return Collection<int, BaseItem>
     */
    private function getBaseItems(string $connection): Collection
    {
        return BaseItem::on($connection)
            ->select([
                'base_items.id',
                'base_items.name',
                'base_items.src',
                'base_items.category',
                'base_items.rarity',
                'base_items.attributes',
                'usage_view.is_in_use as usage_is_in_use',
                'usage_view.source_count as usage_source_count',
            ])
            ->leftJoin('base_item_usage_views as usage_view', 'usage_view.base_item_id', '=', 'base_items.id')
            ->whereNull('base_items.deleted_at')
            ->orderBy('base_items.id')
            ->get();
    }

    /**
     * @param  Collection<int, BaseItem>  $baseItems
     * @return Collection<int, array<string, mixed>>
     */
    private function buildRows(Collection $baseItems, \DateTimeInterface $now): Collection
    {
        return $baseItems
            ->map(fn (BaseItem $baseItem): array => $this->mapComparableItem($baseItem))
            ->groupBy(fn (array $item): string => $item['duplicate_group_hash'])
            ->flatMap(function (Collection $group) use ($now): array {
                $usedItem = $group
                    ->where('is_in_use', true)
                    ->sortBy([
                        ['usage_source_count', 'desc'],
                        ['id', 'asc'],
                    ])
                    ->first();

                if ($usedItem === null) {
                    return [];
                }

                return $group
                    ->where('is_in_use', false)
                    ->sortBy('id')
                    ->map(fn (array $duplicateItem): array => $this->mapDuplicateRow($duplicateItem, $usedItem, $now))
                    ->values()
                    ->all();
            })
            ->values();
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     normalized_name: string,
     *     src: string,
     *     category: string|null,
     *     rarity: string|null,
     *     need_level: int|null,
     *     is_in_use: bool,
     *     usage_source_count: int,
     *     duplicate_group_hash: string
     * }
     */
    private function mapComparableItem(BaseItem $baseItem): array
    {
        $name = Str::squish((string) $baseItem->name);
        $category = $baseItem->getRawOriginal('category');
        $rarity = $baseItem->getRawOriginal('rarity');
        $needLevel = $this->extractNeedLevel($baseItem->attributes);

        $group = [
            'name' => Str::lower($name),
            'category' => $category,
            'rarity' => $rarity,
            'need_level' => $needLevel,
        ];

        return [
            'id' => (int) $baseItem->id,
            'name' => $name,
            'normalized_name' => $group['name'],
            'src' => (string) $baseItem->src,
            'category' => $category,
            'rarity' => $rarity,
            'need_level' => $needLevel,
            'is_in_use' => (bool) $baseItem->getAttribute('usage_is_in_use'),
            'usage_source_count' => (int) ($baseItem->getAttribute('usage_source_count') ?? 0),
            'duplicate_group_hash' => sha1(json_encode($group, JSON_THROW_ON_ERROR)),
        ];
    }

    private function extractNeedLevel(mixed $attributes): ?int
    {
        if (! is_array($attributes)) {
            return null;
        }

        $needLevel = $attributes['needLevel'] ?? null;

        if (! is_numeric($needLevel)) {
            return null;
        }

        return (int) $needLevel;
    }

    /**
     * @param  array<string, mixed>  $duplicateItem
     * @param  array<string, mixed>  $usedItem
     * @return array<string, mixed>
     */
    private function mapDuplicateRow(array $duplicateItem, array $usedItem, \DateTimeInterface $now): array
    {
        return [
            'duplicate_base_item_id' => $duplicateItem['id'],
            'used_base_item_id' => $usedItem['id'],
            'duplicate_group_hash' => $duplicateItem['duplicate_group_hash'],
            'normalized_name' => $duplicateItem['normalized_name'],
            'name' => $duplicateItem['name'],
            'category' => $duplicateItem['category'],
            'rarity' => $duplicateItem['rarity'],
            'need_level' => $duplicateItem['need_level'],
            'duplicate_src' => $duplicateItem['src'],
            'used_src' => $usedItem['src'],
            'duplicate_usage_source_count' => $duplicateItem['usage_source_count'],
            'used_usage_source_count' => $usedItem['usage_source_count'],
            'refreshed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}

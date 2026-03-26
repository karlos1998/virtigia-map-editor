<?php

namespace App\Services;

use App\Models\BaseItem;
use App\Models\BaseItemUsageView;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BaseItemUsageViewService
{
    private const int MAX_SOURCES_PER_ITEM = 20;

    public function clear(string $connection): void
    {
        BaseItemUsageView::on($connection)->delete();
    }

    public function cacheDialogItemIds(string $connection): string
    {
        $cacheKey = "base_item_usage_view:{$connection}:dialog_item_ids:".Str::uuid();

        Cache::store('redis')->put($cacheKey, $this->extractDialogItemIds($connection), 7200);

        return $cacheKey;
    }

    public function refreshChunk(string $connection, int $chunkIndex, int $chunkSize, array $dialogItemIds = []): void
    {
        $itemIds = BaseItem::on($connection)
            ->orderBy('id')
            ->skip($chunkIndex * $chunkSize)
            ->take($chunkSize)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($itemIds->isEmpty()) {
            return;
        }

        $sourcesByItem = $this->groupSources(
            $this->getShopSourceRows($connection, $itemIds)
                ->merge($this->getLootSourceRows($connection, $itemIds))
        );
        $inUseItemIds = $this->resolveInUseItemIds($connection, $itemIds, $sourcesByItem->keys(), collect($dialogItemIds));
        $now = now();

        $payload = $itemIds->map(function (int $itemId) use ($inUseItemIds, $now, $sourcesByItem): array {
            $sources = $sourcesByItem->get($itemId, []);

            return [
                'base_item_id' => $itemId,
                'is_in_use' => $inUseItemIds->contains($itemId),
                'source_count' => count($sources),
                'sources' => empty($sources) ? null : json_encode($sources, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        });

        foreach ($payload->chunk(500) as $chunk) {
            BaseItemUsageView::on($connection)->upsert(
                $chunk->all(),
                ['base_item_id'],
                ['is_in_use', 'source_count', 'sources', 'updated_at']
            );
        }
    }

    public function groupSources(Collection $rows): Collection
    {
        return $rows
            ->groupBy(fn ($row) => (int) $row->base_item_id)
            ->map(function (Collection $itemRows): array {
                $sources = [];
                $seen = [];

                foreach ($itemRows as $row) {
                    $key = $this->createSourceKey($row);

                    if (isset($seen[$key])) {
                        continue;
                    }

                    $seen[$key] = true;
                    $sources[] = $row->source_type === 'loot'
                        ? $this->mapLootSourceRow($row)
                        : $this->mapShopSourceRow($row);

                    if (count($sources) >= self::MAX_SOURCES_PER_ITEM) {
                        break;
                    }
                }

                return $sources;
            });
    }

    public function mapShopSourceRow(object $row): array
    {
        return [
            'type' => 'shop',
            'shop' => [
                'id' => (int) $row->shop_id,
                'name' => $row->shop_name,
            ],
            'dialog' => $row->dialog_id !== null ? [
                'id' => (int) $row->dialog_id,
                'name' => $row->dialog_name,
            ] : null,
            'npc' => $row->base_npc_id !== null ? [
                'id' => (int) $row->base_npc_id,
                'name' => $row->base_npc_name,
                'src' => $row->base_npc_src,
            ] : null,
            'location' => $row->map_id !== null && $row->x !== null && $row->y !== null ? [
                'map_id' => (int) $row->map_id,
                'map_name' => $row->map_name,
                'x' => (int) $row->x,
                'y' => (int) $row->y,
                'label' => $this->formatLocationLabel($row->map_name, (int) $row->map_id, (int) $row->x, (int) $row->y),
            ] : null,
        ];
    }

    public function mapLootSourceRow(object $row): array
    {
        return [
            'type' => 'loot',
            'shop' => null,
            'dialog' => null,
            'npc' => $row->base_npc_id !== null ? [
                'id' => (int) $row->base_npc_id,
                'name' => $row->base_npc_name,
                'src' => $row->base_npc_src,
            ] : null,
            'location' => $row->map_id !== null && $row->x !== null && $row->y !== null ? [
                'map_id' => (int) $row->map_id,
                'map_name' => $row->map_name,
                'x' => (int) $row->x,
                'y' => (int) $row->y,
                'label' => $this->formatLocationLabel($row->map_name, (int) $row->map_id, (int) $row->x, (int) $row->y),
            ] : null,
        ];
    }

    public function formatLocationLabel(string $mapName, int $mapId, int $x, int $y): string
    {
        return "{$mapName} ({$mapId}) x: {$x}, y: {$y}";
    }

    public function extractItemIdsFromPayload(mixed $payload): array
    {
        $itemIds = [];

        $this->collectItemIdsFromPayload($payload, $itemIds);

        return collect($itemIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function getShopSourceRows(string $connection, Collection $itemIds): Collection
    {
        return DB::connection($connection)
            ->table('shop_items')
            ->join('shops', 'shops.id', '=', 'shop_items.shop_id')
            ->leftJoin('dialog_nodes', 'dialog_nodes.shop_id', '=', 'shops.id')
            ->leftJoin('dialogs', 'dialogs.id', '=', 'dialog_nodes.source_dialog_id')
            ->leftJoin('npcs', function (JoinClause $join): void {
                $join->on('npcs.dialog_id', '=', 'dialogs.id')
                    ->where('npcs.enabled', '=', true);
            })
            ->leftJoin('base_npcs', 'base_npcs.id', '=', 'npcs.base_npc_id')
            ->leftJoin('npc_locations', 'npc_locations.npc_id', '=', 'npcs.id')
            ->leftJoin('maps', 'maps.id', '=', 'npc_locations.map_id')
            ->select([
                DB::raw("'shop' as source_type"),
                'shop_items.item_id as base_item_id',
                'shops.id as shop_id',
                'shops.name as shop_name',
                'dialogs.id as dialog_id',
                'dialogs.name as dialog_name',
                'base_npcs.id as base_npc_id',
                'base_npcs.name as base_npc_name',
                'base_npcs.src as base_npc_src',
                'maps.id as map_id',
                'maps.name as map_name',
                'npc_locations.x',
                'npc_locations.y',
            ])
            ->whereIn('shop_items.item_id', $itemIds->all())
            ->orderBy('shop_items.item_id')
            ->orderBy('shops.id')
            ->get();
    }

    private function getLootSourceRows(string $connection, Collection $itemIds): Collection
    {
        return DB::connection($connection)
            ->table('base_npc_loots')
            ->join('base_npcs', 'base_npcs.id', '=', 'base_npc_loots.base_npc_id')
            ->leftJoin('npcs', function (JoinClause $join): void {
                $join->on('npcs.base_npc_id', '=', 'base_npcs.id')
                    ->where('npcs.enabled', '=', true);
            })
            ->leftJoin('npc_locations', 'npc_locations.npc_id', '=', 'npcs.id')
            ->leftJoin('maps', 'maps.id', '=', 'npc_locations.map_id')
            ->select([
                DB::raw("'loot' as source_type"),
                'base_npc_loots.base_item_id as base_item_id',
                DB::raw('NULL as shop_id'),
                DB::raw('NULL as shop_name'),
                DB::raw('NULL as dialog_id'),
                DB::raw('NULL as dialog_name'),
                'base_npcs.id as base_npc_id',
                'base_npcs.name as base_npc_name',
                'base_npcs.src as base_npc_src',
                'maps.id as map_id',
                'maps.name as map_name',
                'npc_locations.x',
                'npc_locations.y',
            ])
            ->whereIn('base_npc_loots.base_item_id', $itemIds->all())
            ->orderBy('base_npc_loots.base_item_id')
            ->orderBy('base_npcs.id')
            ->get();
    }

    private function resolveInUseItemIds(
        string $connection,
        Collection $itemIds,
        Collection $itemsWithShopSources,
        Collection $dialogItemIds,
    ): Collection {
        $shopItemIds = DB::connection($connection)
            ->table('shop_items')
            ->whereIn('item_id', $itemIds->all())
            ->distinct()
            ->pluck('item_id');

        $lootItemIds = DB::connection($connection)
            ->table('base_npc_loots')
            ->whereIn('base_item_id', $itemIds->all())
            ->distinct()
            ->pluck('base_item_id');

        return $itemsWithShopSources
            ->merge($shopItemIds)
            ->merge($lootItemIds)
            ->merge($dialogItemIds->intersect($itemIds))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function extractDialogItemIds(string $connection): array
    {
        $itemIds = [];

        DB::connection($connection)
            ->table('dialog_node_options')
            ->select(['id', 'rules'])
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$itemIds): void {
                foreach ($rows as $row) {
                    $payload = json_decode($row->rules ?? 'null', true);
                    $itemIds = [...$itemIds, ...$this->extractItemIdsFromPayload($payload)];
                }
            });

        DB::connection($connection)
            ->table('dialog_nodes')
            ->whereNotNull('additional_actions')
            ->select(['id', 'additional_actions'])
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use (&$itemIds): void {
                foreach ($rows as $row) {
                    $payload = json_decode($row->additional_actions ?? 'null', true);
                    $itemIds = [...$itemIds, ...$this->extractItemIdsFromPayload($payload)];
                }
            });

        return collect($itemIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function collectItemIdsFromPayload(mixed $payload, array &$itemIds): void
    {
        if (! is_array($payload)) {
            return;
        }

        foreach ($payload as $key => $value) {
            if (in_array($key, ['items', 'addItems'], true) && is_array($value) && array_key_exists('value', $value)) {
                $this->appendItemIds($value['value'], $itemIds);
            }

            $this->collectItemIdsFromPayload($value, $itemIds);
        }
    }

    private function appendItemIds(mixed $value, array &$itemIds): void
    {
        if (is_numeric($value)) {
            $itemIds[] = (int) $value;

            return;
        }

        if (! is_array($value)) {
            return;
        }

        foreach ($value as $entry) {
            $this->appendItemIds($entry, $itemIds);
        }
    }

    private function createSourceKey(object $row): string
    {
        return implode('|', [
            (string) $row->source_type,
            (int) $row->shop_id,
            (int) ($row->dialog_id ?? 0),
            (int) ($row->base_npc_id ?? 0),
            (int) ($row->map_id ?? 0),
            (string) ($row->x ?? ''),
            (string) ($row->y ?? ''),
        ]);
    }
}

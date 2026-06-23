<?php

namespace App\Console\Commands;

use App\Models\BaseItem;
use App\Services\BaseItemUsageViewService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DebugBaseItemUsageViewCommand extends Command
{
    protected $signature = 'base-item-usage-view:debug
        {id : Base item ID}
        {--world=retro : World name}
        {--chunk-size=500 : Number of base items per chunk}';

    protected $description = 'Refresh and inspect cached usage data for a single base item';

    public function handle(BaseItemUsageViewService $baseItemUsageViewService): int
    {
        $world = (string) $this->option('world');
        $baseItemId = (int) $this->argument('id');
        $chunkSize = max(1, (int) $this->option('chunk-size'));

        if (! in_array($world, ['retro', 'legacy'], true)) {
            $this->error("Unsupported world [{$world}]. Allowed values: retro, legacy.");

            return self::FAILURE;
        }

        $baseItem = BaseItem::on($world)->withTrashed()->find($baseItemId);

        if (! $baseItem) {
            $this->error("Base item [{$baseItemId}] was not found in [{$world}].");

            return self::FAILURE;
        }

        $this->info("Base item usage debug for [{$world}] item [{$baseItemId}]");
        $this->table(['Field', 'Value'], [
            ['ID', (string) $baseItem->id],
            ['Name', (string) $baseItem->name],
            ['Deleted at', (string) ($baseItem->deleted_at ?? '-')],
        ]);

        $usageBefore = $this->getUsageRow($world, $baseItemId);
        $directUsage = $this->getDirectUsage($world, $baseItemId, $baseItemUsageViewService);

        $this->line('Usage cache before refresh:');
        $this->table(['is_in_use', 'source_count', 'updated_at', 'sources'], [
            $this->formatUsageRow($usageBefore),
        ]);

        $this->line('Direct usage signals:');
        $this->table(['Signal', 'Count', 'Sample'], [
            ['base_npc_loots', (string) $directUsage['loot_count'], $directUsage['loot_sample']],
            ['shop_items', (string) $directUsage['shop_count'], $directUsage['shop_sample']],
            ['dialog_node_options.rules', (string) $directUsage['dialog_rules_count'], $directUsage['dialog_rules_sample']],
            ['dialog_node_options.additional_actions', (string) $directUsage['dialog_option_actions_count'], $directUsage['dialog_option_actions_sample']],
            ['dialog_nodes.additional_actions', (string) $directUsage['dialog_actions_count'], $directUsage['dialog_actions_sample']],
        ]);

        if ($baseItem->deleted_at !== null) {
            $this->warn('Item is soft-deleted. The usage refresh skips soft-deleted base items.');

            return self::SUCCESS;
        }

        $position = BaseItem::on($world)
            ->where('id', '<=', $baseItemId)
            ->count();
        $chunkIndex = intdiv(max(0, $position - 1), $chunkSize);

        $this->line("Refreshing chunk [{$chunkIndex}] with chunk size [{$chunkSize}] for position [{$position}].");

        $dialogItemIdsCacheKey = $baseItemUsageViewService->cacheDialogItemIds($world);

        try {
            $dialogItemIds = Cache::store('redis')->get($dialogItemIdsCacheKey, []);

            $baseItemUsageViewService->refreshChunk(
                $world,
                $chunkIndex,
                $chunkSize,
                is_array($dialogItemIds) ? $dialogItemIds : []
            );
        } finally {
            Cache::store('redis')->forget($dialogItemIdsCacheKey);
        }

        $usageAfter = $this->getUsageRow($world, $baseItemId);
        $expectedInUse = $this->isExpectedInUse($directUsage);
        $actualInUse = $usageAfter !== null && (bool) $usageAfter->is_in_use;

        $this->line('Usage cache after refresh:');
        $this->table(['is_in_use', 'source_count', 'updated_at', 'sources'], [
            $this->formatUsageRow($usageAfter),
        ]);

        $this->line('Cached sources after refresh:');
        $sourceRows = $this->formatSourceRows($usageAfter?->sources);
        $this->table(['Type', 'NPC', 'Shop', 'Dialog', 'Location'], $sourceRows ?: [['-', '-', '-', '-', '-']]);

        if ($expectedInUse !== $actualInUse) {
            $this->warn(sprintf(
                'Mismatch: direct data says [%s], refreshed cache says [%s].',
                $expectedInUse ? 'in use' : 'not in use',
                $actualInUse ? 'in use' : 'not in use'
            ));

            return self::FAILURE;
        }

        $this->info(sprintf(
            'OK: direct data and refreshed cache both say [%s].',
            $actualInUse ? 'in use' : 'not in use'
        ));

        return self::SUCCESS;
    }

    private function getUsageRow(string $world, int $baseItemId): ?object
    {
        return DB::connection($world)
            ->table('base_item_usage_views')
            ->where('base_item_id', $baseItemId)
            ->first();
    }

    /**
     * @return array{
     *     loot_count: int,
     *     loot_sample: string,
     *     shop_count: int,
     *     shop_sample: string,
     *     dialog_rules_count: int,
     *     dialog_rules_sample: string,
     *     dialog_option_actions_count: int,
     *     dialog_option_actions_sample: string,
     *     dialog_actions_count: int,
     *     dialog_actions_sample: string
     * }
     */
    private function getDirectUsage(string $world, int $baseItemId, BaseItemUsageViewService $baseItemUsageViewService): array
    {
        $lootRows = DB::connection($world)
            ->table('base_npc_loots')
            ->join('base_npcs', 'base_npcs.id', '=', 'base_npc_loots.base_npc_id')
            ->where('base_npc_loots.base_item_id', $baseItemId)
            ->orderBy('base_npcs.id')
            ->limit(10)
            ->get([
                'base_npcs.id',
                'base_npcs.name',
            ]);

        $shopRows = DB::connection($world)
            ->table('shop_items')
            ->join('shops', 'shops.id', '=', 'shop_items.shop_id')
            ->where('shop_items.item_id', $baseItemId)
            ->orderBy('shops.id')
            ->limit(10)
            ->get([
                'shops.id',
                'shops.name',
            ]);

        $dialogRules = $this->collectDialogPayloadHits(
            $world,
            'dialog_node_options',
            'rules',
            $baseItemId,
            $baseItemUsageViewService
        );
        $dialogOptionActions = $this->collectDialogPayloadHits(
            $world,
            'dialog_node_options',
            'additional_actions',
            $baseItemId,
            $baseItemUsageViewService
        );
        $dialogNodeActions = $this->collectDialogPayloadHits(
            $world,
            'dialog_nodes',
            'additional_actions',
            $baseItemId,
            $baseItemUsageViewService
        );

        return [
            'loot_count' => DB::connection($world)
                ->table('base_npc_loots')
                ->where('base_item_id', $baseItemId)
                ->count(),
            'loot_sample' => $this->formatNamedRows($lootRows),
            'shop_count' => DB::connection($world)
                ->table('shop_items')
                ->where('item_id', $baseItemId)
                ->count(),
            'shop_sample' => $this->formatNamedRows($shopRows),
            'dialog_rules_count' => $dialogRules['count'],
            'dialog_rules_sample' => $dialogRules['sample'],
            'dialog_option_actions_count' => $dialogOptionActions['count'],
            'dialog_option_actions_sample' => $dialogOptionActions['sample'],
            'dialog_actions_count' => $dialogNodeActions['count'],
            'dialog_actions_sample' => $dialogNodeActions['sample'],
        ];
    }

    /**
     * @return array{count: int, sample: string}
     */
    private function collectDialogPayloadHits(
        string $world,
        string $table,
        string $column,
        int $baseItemId,
        BaseItemUsageViewService $baseItemUsageViewService,
    ): array {
        $count = 0;
        $sample = [];

        DB::connection($world)
            ->table($table)
            ->whereNotNull($column)
            ->select(['id', $column])
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use ($column, $baseItemId, $baseItemUsageViewService, &$count, &$sample): void {
                foreach ($rows as $row) {
                    $payload = json_decode($row->{$column} ?? 'null', true);
                    $itemIds = $baseItemUsageViewService->extractItemIdsFromPayload($payload);

                    if (! in_array($baseItemId, $itemIds, true)) {
                        continue;
                    }

                    $count++;

                    if (count($sample) < 10) {
                        $sample[] = (int) $row->id;
                    }
                }
            });

        return [
            'count' => $count,
            'sample' => empty($sample) ? '-' : implode(', ', $sample),
        ];
    }

    /**
     * @param  array{
     *     loot_count: int,
     *     shop_count: int,
     *     dialog_rules_count: int,
     *     dialog_option_actions_count: int,
     *     dialog_actions_count: int
     * }  $directUsage
     */
    private function isExpectedInUse(array $directUsage): bool
    {
        return $directUsage['loot_count'] > 0
            || $directUsage['shop_count'] > 0
            || $directUsage['dialog_rules_count'] > 0
            || $directUsage['dialog_option_actions_count'] > 0
            || $directUsage['dialog_actions_count'] > 0;
    }

    /**
     * @return array{string, string, string, string}
     */
    private function formatUsageRow(?object $usageRow): array
    {
        if ($usageRow === null) {
            return ['missing', '-', '-', '-'];
        }

        return [
            (bool) $usageRow->is_in_use ? 'yes' : 'no',
            (string) $usageRow->source_count,
            (string) $usageRow->updated_at,
            $usageRow->sources === null ? '-' : 'present',
        ];
    }

    /**
     * @return array<int, array{string, string, string, string, string}>
     */
    private function formatSourceRows(?string $sources): array
    {
        if ($sources === null || $sources === '') {
            return [];
        }

        $decodedSources = json_decode($sources, true);

        if (! is_array($decodedSources)) {
            return [];
        }

        return collect($decodedSources)
            ->take(10)
            ->map(fn (array $source): array => [
                (string) ($source['type'] ?? '-'),
                (string) ($source['npc']['name'] ?? '-'),
                (string) ($source['shop']['name'] ?? '-'),
                (string) ($source['dialog']['name'] ?? '-'),
                (string) ($source['location']['label'] ?? '-'),
            ])
            ->values()
            ->all();
    }

    private function formatNamedRows(Collection $rows): string
    {
        if ($rows->isEmpty()) {
            return '-';
        }

        return $rows
            ->map(fn (object $row): string => sprintf('#%s %s', $row->id, $row->name ?? '-'))
            ->implode(', ');
    }
}

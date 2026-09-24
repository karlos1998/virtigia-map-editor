<?php

namespace App\Services\Mcp;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Shop;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RetroBuildOptionsService
{
    /** @var array<string, int> */
    private const array RARITY_ORDER = [
        'common' => 0,
        'unique' => 1,
        'heroic' => 2,
        'upgraded' => 3,
        'legendary' => 4,
        'artefact' => 5,
    ];

    public function __construct(
        private readonly RetroEngineAnalysisService $analysisService,
        private readonly McpWorldService $worldService,
    ) {}

    /**
     * @param  array{max_rarity?: string|null, obtainable_only?: bool, allowed_sources?: array<int, string>, exclude_event_sources?: bool, exclude_admin_shops?: bool}  $filters
     * @return array<string, mixed>
     */
    public function get(
        string $world,
        int $level,
        string $profession,
        int $perCategory,
        array $filters,
    ): array {
        $this->worldService->use($world);
        $equipment = $this->analysisService->equipment($level, $profession, $perCategory);
        $itemsByCategory = collect($equipment['itemsByCategory'] ?? []);
        $itemIds = $itemsByCategory->flatten(1)->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values();

        $items = BaseItem::query()
            ->with('usageView')
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $rawSources = $items->flatMap(fn (BaseItem $item): array => $item->usageView?->sources ?? []);
        $baseNpcs = BaseNpc::query()
            ->with('seasonalEvents:id,name,slug,active,starts_at,ends_at')
            ->whereIn('id', $rawSources->pluck('npc.id')->filter()->unique())
            ->get()
            ->keyBy('id');
        $shops = Shop::query()
            ->whereIn('id', $rawSources->pluck('shop.id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $filteredCategories = $itemsByCategory
            ->map(function (array $categoryItems) use ($baseNpcs, $filters, $items, $shops): array {
                return collect($categoryItems)
                    ->map(function (array $engineItem) use ($baseNpcs, $items, $shops): array {
                        $item = $items->get((int) $engineItem['id']);
                        $sources = collect($item?->usageView?->sources ?? [])
                            ->map(fn (array $source): array => $this->enrichSource($source, $baseNpcs, $shops, $engineItem))
                            ->values();

                        $engineItem['sources'] = $sources->all();
                        $engineItem['sourceKinds'] = $sources->pluck('kind')->unique()->values()->all();
                        $engineItem['obtainable'] = $sources->contains(fn (array $source): bool => ! $source['flags']['admin'] && ! $source['flags']['test']);

                        return $engineItem;
                    })
                    ->map(fn (array $item): ?array => $this->applyFilters($item, $filters))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter(fn (array $categoryItems): bool => $categoryItems !== [])
            ->all();

        $equipment['itemsByCategory'] = $filteredCategories;
        $equipment['availabilityFilters'] = $filters;
        $equipment['availabilityGuidance'] = [
            'An item is obtainable only when at least one returned source is neither administrative nor test content.',
            'Event sources are marked explicitly and may be excluded independently.',
            'Use source kind, NPC rank and level, map, shop and currency fields when explaining where an item comes from.',
        ];

        return [
            'equipment' => $equipment,
            'skills' => $this->analysisService->skills($level, $profession),
        ];
    }

    /**
     * @param  Collection<int, BaseNpc>  $baseNpcs
     * @param  Collection<int, Shop>  $shops
     * @param  array<string, mixed>  $engineItem
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private function enrichSource(array $source, Collection $baseNpcs, Collection $shops, array $engineItem): array
    {
        $baseNpc = $baseNpcs->get((int) data_get($source, 'npc.id'));
        $shop = $shops->get((int) data_get($source, 'shop.id'));
        $searchableName = Str::lower(collect([
            data_get($source, 'shop.name'),
            data_get($source, 'dialog.name'),
            data_get($source, 'npc.name'),
            data_get($source, 'location.map_name'),
        ])->filter()->implode(' '));
        $isAdmin = Str::contains($searchableName, ['siedziba mg', 'administr']);
        $isTest = Str::contains($searchableName, ['testowy', 'testowa', 'testowe', 'test ']);
        $isEvent = $baseNpc?->seasonalEvents->isNotEmpty() ?? false;
        $rank = $baseNpc?->rank?->value ?? $baseNpc?->rank;

        if ($baseNpc !== null) {
            $source['npc']['level'] = $baseNpc->lvl;
            $source['npc']['rank'] = $rank;
            $source['npc']['seasonal_events'] = $baseNpc->seasonalEvents->map(fn ($event): array => [
                'id' => $event->id,
                'name' => $event->name,
                'active' => $event->isCurrentlyActive(),
            ])->values()->all();
        }
        if (($source['type'] ?? null) === 'shop') {
            $source['shop']['currency'] = $engineItem['currency'] ?? null;
            $source['shop']['item_price'] = $engineItem['price'] ?? null;
            $source['shop']['currency_item_id'] = $shop?->currency_item_id;
        }
        $source['kind'] = $this->sourceKind($source['type'] ?? '', $rank, $isAdmin, $isTest, $engineItem['currency'] ?? null);
        $source['flags'] = [
            'admin' => $isAdmin,
            'test' => $isTest,
            'event' => $isEvent,
        ];

        return $source;
    }

    private function sourceKind(string $type, mixed $rank, bool $isAdmin, bool $isTest, mixed $currency): string
    {
        if ($type === 'shop') {
            if ($isAdmin) {
                return 'admin_shop';
            }
            if ($isTest) {
                return 'test_shop';
            }

            return $currency === 'gold' ? 'gold_shop' : 'currency_shop';
        }

        return match ((string) $rank) {
            'NORMAL' => 'normal_mob',
            'ELITE' => 'elite',
            'ELITE_II' => 'elite_2',
            'ELITE_III' => 'elite_3',
            'HERO' => 'hero',
            'TITAN' => 'titan',
            default => 'unknown_loot',
        };
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $filters @return array<string, mixed>|null */
    private function applyFilters(array $item, array $filters): ?array
    {
        $maximumRarity = $filters['max_rarity'] ?? null;
        if ($maximumRarity !== null
            && (self::RARITY_ORDER[$item['rarity'] ?? 'common'] ?? PHP_INT_MAX) > self::RARITY_ORDER[$maximumRarity]) {
            return null;
        }

        $sources = collect($item['sources']);
        if ($filters['exclude_event_sources'] ?? false) {
            $sources = $sources->reject(fn (array $source): bool => $source['flags']['event']);
        }
        if ($filters['exclude_admin_shops'] ?? false) {
            $sources = $sources->reject(fn (array $source): bool => $source['flags']['admin'] || $source['flags']['test']);
        }
        if (($filters['allowed_sources'] ?? []) !== []) {
            $sources = $sources->filter(fn (array $source): bool => in_array($source['kind'], $filters['allowed_sources'], true));
        }

        $item['sources'] = $sources->values()->all();
        $item['sourceKinds'] = $sources->pluck('kind')->unique()->values()->all();
        $item['obtainable'] = $sources->contains(fn (array $source): bool => ! $source['flags']['admin'] && ! $source['flags']['test']);

        if (($filters['obtainable_only'] ?? false) && ! $item['obtainable']) {
            return null;
        }

        return $item;
    }
}

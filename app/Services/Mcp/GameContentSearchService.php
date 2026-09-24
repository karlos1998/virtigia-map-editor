<?php

namespace App\Services\Mcp;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Facades\AssetUrl;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Models\Map as GameMap;
use App\Models\Npc;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Shop;

class GameContentSearchService
{
    public function __construct(
        private readonly McpWorldService $worldService,
    ) {}

    /**
     * @param  array<int, string>  $types
     * @return array<string, mixed>
     */
    public function search(string $world, string $query, array $types, ?string $mapName, int $limit): array
    {
        $world = $this->worldService->use($world);
        $types = $types === [] ? ['maps', 'npcs', 'base_npcs', 'items', 'shops', 'quests', 'dialogs'] : $types;
        $limit = min(max($limit, 1), 25);
        $like = '%'.trim($query).'%';
        $results = [];

        if (in_array('maps', $types, true)) {
            $results['maps'] = GameMap::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'src', 'x', 'y'])
                ->toArray();
        }

        if (in_array('base_npcs', $types, true)) {
            $results['base_npcs'] = BaseNpc::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'src', 'lvl', 'category', 'rank'])
                ->toArray();
        }

        if (in_array('items', $types, true)) {
            $results['items'] = BaseItem::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'src', 'category'])
                ->toArray();
        }

        if (in_array('shops', $types, true)) {
            $results['shops'] = Shop::query()
                ->select(['id', 'name', 'currency_item_id'])
                ->withCount('items')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->toArray();
        }

        if (in_array('npcs', $types, true)) {
            $results['npcs'] = Npc::query()
                ->with([
                    'base:id,name,src',
                    'dialog:id,name',
                    'locations.map:id,name',
                ])
                ->whereHas('base', fn ($npcQuery) => $npcQuery->where('name', 'like', $like))
                ->when($mapName, fn ($npcQuery, string $name) => $npcQuery->whereHas(
                    'locations.map',
                    fn ($mapQuery) => $mapQuery->where('name', 'like', '%'.$name.'%'),
                ))
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map(fn (Npc $npc): array => [
                    'id' => $npc->id,
                    'name' => $npc->base?->name,
                    'base_npc_id' => $npc->base_npc_id,
                    'base_npc_src' => $npc->base?->src,
                    'dialog' => $npc->dialog ? ['id' => $npc->dialog->id, 'name' => $npc->dialog->name] : null,
                    'locations' => $npc->locations->map(fn ($location): array => [
                        'id' => $location->id,
                        'map_id' => $location->map_id,
                        'map_name' => $location->map?->name,
                        'x' => $location->x,
                        'y' => $location->y,
                    ])->all(),
                ])
                ->all();
        }

        if (in_array('quests', $types, true)) {
            $results['quests'] = Quest::query()
                ->with('steps:id,quest_id,name,description,visible_in_quest_list')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name'])
                ->toArray();
        }

        if (in_array('dialogs', $types, true)) {
            $results['dialogs'] = Dialog::query()
                ->with(['npcs.base:id,name', 'npcs.locations.map:id,name'])
                ->withCount(['nodes', 'edges'])
                ->where(function ($dialogQuery) use ($like): void {
                    $dialogQuery->where('name', 'like', $like)
                        ->orWhereHas('npcs.base', fn ($baseNpcQuery) => $baseNpcQuery->where('name', 'like', $like));
                })
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn (Dialog $dialog): array => [
                    'id' => $dialog->id,
                    'name' => $dialog->name,
                    'nodes_count' => $dialog->nodes_count,
                    'edges_count' => $dialog->edges_count,
                    'npcs' => $dialog->npcs->map(fn (Npc $npc): array => [
                        'id' => $npc->id,
                        'name' => $npc->base?->name,
                        'maps' => $npc->locations->pluck('map.name')->filter()->unique()->values()->all(),
                    ])->all(),
                ])
                ->all();
        }

        return [
            'world' => $world,
            'query' => $query,
            'results' => $results,
        ];
    }

    /** @return array<string, mixed> */
    public function baseItem(string $world, int $baseItemId): array
    {
        $world = $this->worldService->use($world);
        $baseItem = BaseItem::query()
            ->with(['shops', 'baseNpcs:id,name,lvl,rank', 'usageView'])
            ->findOrFail($baseItemId);

        return [
            'world' => $world,
            'item' => [
                'id' => $baseItem->id,
                'name' => $baseItem->name,
                'src' => $baseItem->src,
                'image_url' => AssetUrl::item($baseItem->src),
                'category' => $baseItem->category?->value,
                'rarity' => $baseItem->rarity,
                'price' => $baseItem->price,
                'currency' => $baseItem->currency?->value,
                'specific_currency_price' => $baseItem->specific_currency_price,
                'attributes' => $baseItem->attributes ?? [],
                'attribute_points' => $baseItem->attribute_points ?? [],
                'manual_attribute_points' => $baseItem->manual_attribute_points ?? [],
                'reverse_attributes' => $baseItem->reverse_attributes ?? [],
                'shops' => $baseItem->shops
                    ->sortBy('pivot.position')
                    ->map(fn (Shop $shop): array => [
                        'id' => $shop->id,
                        'name' => $shop->name,
                        'position' => (int) $shop->pivot->position,
                        'row' => intdiv((int) $shop->pivot->position, 8),
                        'column' => (int) $shop->pivot->position % 8,
                    ])->values()->all(),
                'base_npc_loots' => $baseItem->baseNpcs->map(fn (BaseNpc $baseNpc): array => [
                    'id' => $baseNpc->id,
                    'name' => $baseNpc->name,
                    'level' => $baseNpc->lvl,
                    'rank' => $baseNpc->rank?->value,
                ])->values()->all(),
                'usage_sources' => $baseItem->usageView?->sources ?? [],
            ],
            'editing' => [
                'categories' => BaseItemCategory::valuesToList(),
                'rarities' => BaseItemRarity::valuesToList(),
                'currencies' => BaseItemCurrency::valuesToList(),
                'image' => ['formats' => ['png', 'gif'], 'width' => 32, 'height' => 32],
                'guidance' => 'For a percentage improvement, calculate and show the exact changed numeric attributes. Preserve unrelated attributes and use attributes_patch/remove_attributes for targeted edits.',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function shopInventory(string $world, int $shopId): array
    {
        $world = $this->worldService->use($world);
        $shop = Shop::query()->with(['items' => fn ($query) => $query->orderBy('shop_items.position')])->findOrFail($shopId);
        $occupiedPositions = $shop->items->pluck('pivot.position')->map(fn ($value): int => (int) $value)->all();

        return [
            'world' => $world,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'currency_item_id' => $shop->currency_item_id,
                'grid' => ['rows' => 10, 'columns' => 8, 'minimum_position' => 0, 'maximum_position' => 79],
                'items' => $shop->items->map(fn (BaseItem $baseItem): array => [
                    'id' => $baseItem->id,
                    'name' => $baseItem->name,
                    'position' => (int) $baseItem->pivot->position,
                    'row' => intdiv((int) $baseItem->pivot->position, 8),
                    'column' => (int) $baseItem->pivot->position % 8,
                ])->values()->all(),
                'occupied_positions' => $occupiedPositions,
                'free_positions' => array_values(array_diff(range(0, 79), $occupiedPositions)),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function quest(string $world, int $questId): array
    {
        $world = $this->worldService->use($world);
        $quest = Quest::query()
            ->with([
                'steps.autoProgress.mobs.baseNpc:id,name,src,lvl,rank',
                'steps.autoProgress.mobs.mobSpecies:id,name',
            ])
            ->findOrFail($questId);

        return [
            'world' => $world,
            'quest' => [
                'id' => $quest->id,
                'name' => $quest->name,
                'steps' => $quest->steps->sortBy('id')->map(fn (QuestStep $step): array => [
                    'id' => $step->id,
                    'name' => $step->name,
                    'description' => $step->description,
                    'visible_in_quest_list' => $step->visible_in_quest_list,
                    'auto_advance_next_day' => $step->auto_advance_next_day,
                    'auto_advance_to_step_id' => $step->auto_advance_to_step_id,
                    'auto_progress' => $step->autoProgress === null ? null : [
                        'type' => $step->autoProgress->type,
                        'time_seconds' => $step->autoProgress->time_seconds,
                        'mobs' => $step->autoProgress->mobs->map(fn ($mob): array => [
                            'type' => $mob->mob_species_id === null ? 'base_npc' : 'mob_species',
                            'base_npc_id' => $mob->base_npc_id,
                            'mob_species_id' => $mob->mob_species_id,
                            'quantity' => $mob->quantity,
                            'base_npc' => $mob->baseNpc === null ? null : [
                                'id' => $mob->baseNpc->id,
                                'name' => $mob->baseNpc->name,
                                'level' => $mob->baseNpc->lvl,
                                'rank' => $mob->baseNpc->rank?->value,
                                'src' => $mob->baseNpc->src,
                                'image_url' => AssetUrl::npc($mob->baseNpc->src),
                            ],
                            'mob_species' => $mob->mobSpecies === null ? null : [
                                'id' => $mob->mobSpecies->id,
                                'name' => $mob->mobSpecies->name,
                            ],
                        ])->values()->all(),
                    ],
                ])->values()->all(),
            ],
            'engine_behavior' => [
                'mobs' => 'Every matching kill increments its target counter. When every target reaches quantity, the engine automatically activates the next quest step by step ID order.',
                'time' => 'After time_seconds, the engine automatically activates the next quest step by step ID order.',
                'next_day' => 'auto_advance_next_day runs on the next daily reset; auto_advance_to_step_id selects the target, while null clears the quest progress.',
                'description_is_not_logic' => true,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function writingContext(string $world, ?string $topic, ?int $npcId, int $limit): array
    {
        $world = $this->worldService->use($world);
        $limit = min(max($limit, 1), 12);

        $nodes = DialogNode::query()
            ->with(['dialog:id,name', 'options:id,node_id,label,rules,additional_actions,order'])
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->when($npcId, fn ($nodeQuery, int $id) => $nodeQuery->whereHas(
                'dialog.npcs',
                fn ($npcQuery) => $npcQuery->whereKey($id),
            ))
            ->when($topic, function ($nodeQuery, string $value): void {
                $like = '%'.$value.'%';
                $nodeQuery->where(function ($topicQuery) use ($like): void {
                    $topicQuery->where('content', 'like', $like)
                        ->orWhereHas('dialog', fn ($dialogQuery) => $dialogQuery->where('name', 'like', $like))
                        ->orWhereHas('options', fn ($optionQuery) => $optionQuery->where('label', 'like', $like));
                });
            })
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($nodes->isEmpty() && $topic !== null) {
            $nodes = DialogNode::query()
                ->with(['dialog:id,name', 'options:id,node_id,label,rules,additional_actions,order'])
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        }

        return [
            'world' => $world,
            'guidance' => [
                'Treat examples as tone references, not text to copy.',
                'Prefer short, natural Polish dialogue with the same light dialect level as nearby NPCs.',
                'Do not copy obvious test, placeholder, or malformed content.',
            ],
            'examples' => $nodes->map(fn (DialogNode $node): array => [
                'dialog_id' => $node->source_dialog_id,
                'dialog_name' => $node->dialog?->name,
                'node_type' => $node->type,
                'content' => $node->content,
                'options' => $node->options->map(fn ($option): array => [
                    'label' => $option->label,
                    'rules' => $option->rules,
                    'additional_actions' => $option->additional_actions,
                ])->all(),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    public function dialogGraph(string $world, int $dialogId): array
    {
        $world = $this->worldService->use($world);
        $dialog = Dialog::query()
            ->with(['nodes.options', 'edges', 'npcs.base:id,name', 'npcs.locations.map:id,name'])
            ->findOrFail($dialogId);

        return [
            'world' => $world,
            'dialog' => [
                'id' => $dialog->id,
                'name' => $dialog->name,
                'shared_by_npcs' => $dialog->npcs->map(fn (Npc $npc): array => [
                    'npc_id' => $npc->id,
                    'base_npc_id' => $npc->base_npc_id,
                    'name' => $npc->base?->name,
                    'maps' => $npc->locations->pluck('map.name')->filter()->unique()->values()->all(),
                ])->values()->all(),
                'nodes' => $dialog->nodes->sortBy('id')->map(fn (DialogNode $node): array => [
                    'id' => $node->id,
                    'type' => $node->type,
                    'position' => $node->position,
                    'content' => $node->content,
                    'action_data' => $node->action_data,
                    'additional_actions' => $node->additional_actions,
                    'shop_id' => $node->shop_id,
                    'hotel_id' => $node->hotel_id,
                    'options' => $node->options->map(fn (DialogNodeOption $option): array => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'rules' => $option->rules,
                        'additional_action' => $option->additional_action?->value,
                        'additional_actions' => $option->additional_actions,
                        'cooldown' => $option->cooldown,
                        'order' => $option->order,
                    ])->values()->all(),
                ])->values()->all(),
                'edges' => $dialog->edges->sortBy('id')->map(fn (DialogEdge $edge): array => [
                    'id' => $edge->id,
                    'source_node_id' => $edge->source_node_id,
                    'source_option_id' => $edge->source_option_id,
                    'source_handle' => $edge->source_handle,
                    'target_node_id' => $edge->target_node_id,
                    'rules' => $edge->rules,
                ])->values()->all(),
            ],
            'editing_guidance' => [
                'Use patch_dialog for additions and targeted edits; do not replace the whole graph.',
                'A shared dialog is intentionally updated for every NPC listed in shared_by_npcs.',
                'Untouched shop_id and hotel_id values are preserved automatically.',
                'Node positions are recalculated after applying the patch to prevent overlap.',
            ],
        ];
    }
}

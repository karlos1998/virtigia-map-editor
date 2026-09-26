<?php

namespace App\Services\Mcp;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Enums\DialogCounterScope;
use App\Enums\Profession;
use App\Facades\AssetUrl;
use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\DialogCounter;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Models\Hotel;
use App\Models\Map as GameMap;
use App\Models\MobSpecies;
use App\Models\Npc;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\SeasonalEvent;
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
        $types = $types === [] ? [
            'maps', 'npcs', 'base_npcs', 'items', 'shops', 'hotels', 'quests', 'dialogs',
            'dialog_counters', 'seasonal_events', 'mob_species',
        ] : $types;
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

        if (in_array('hotels', $types, true)) {
            $results['hotels'] = Hotel::query()
                ->select(['id', 'name', 'currency', 'period'])
                ->withCount('rooms')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->toArray();
        }

        if (in_array('dialog_counters', $types, true)) {
            $results['dialog_counters'] = DialogCounter::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'scope'])
                ->map(fn (DialogCounter $counter): array => [
                    'id' => $counter->id,
                    'name' => $counter->name,
                    'scope' => $counter->scope?->value,
                ])->all();
        }

        if (in_array('seasonal_events', $types, true)) {
            $results['seasonal_events'] = SeasonalEvent::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'slug', 'active', 'starts_at', 'ends_at'])
                ->map(fn (SeasonalEvent $event): array => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'is_currently_active' => $event->isCurrentlyActive(),
                    'starts_at' => $event->starts_at?->toIso8601String(),
                    'ends_at' => $event->ends_at?->toIso8601String(),
                ])->all();
        }

        if (in_array('mob_species', $types, true)) {
            $results['mob_species'] = MobSpecies::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name'])
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
    public function dialogCapabilities(string $world): array
    {
        $world = $this->worldService->use($world);

        return [
            'world' => $world,
            'graph_model' => [
                'option_rules' => 'option.rules decide whether the answer is available to the player.',
                'edge_rules' => 'edge.rules independently decide whether that particular outgoing branch may be followed. One option may lead to several targets with different edge rules.',
                'terminal_option' => 'An option without an outgoing edge closes the conversation.',
                'shared_dialog' => 'Editing one dialog changes it for every NPC that references the same dialog.',
                'layout' => 'After patch_dialog the server recalculates node positions by graph depth and node size, preventing Vue Flow overlap.',
                'patching' => 'Use stable IDs from get_dialog_graph for existing records and local keys for new records. Omitted fields and branches are preserved.',
            ],
            'text_runtime' => [
                'placeholders' => [
                    '#nick' => 'current character name',
                    '#lvl' => 'current character level',
                    '#difflvl(N)' => 'absolute difference between the character level and N',
                ],
                'bbcode' => ['[b]...[/b]', '[i]...[/i]', '[u]...[/u]', '[href=URL]...[/href]', '[img]URL[/img]', '[br]'],
                'html' => 'Raw HTML is stripped by the client. Use supported BBCode only.',
                'message_input' => 'A messageContent rule makes the client ask the player for text and send it with the option click. Its value is the required answer, max 100 characters.',
            ],
            'node_types' => [
                'start' => [
                    'purpose' => 'Invisible graph entry.',
                    'outputs' => 'Direct edges; normally at least one. Rules belong on those edges.',
                ],
                'special' => [
                    'purpose' => 'Visible NPC speech.',
                    'fields' => ['content', 'action_data.focus', 'additional_actions', 'options'],
                    'requirements' => 'At least one option; content is 3-2000 characters when present.',
                ],
                'shop' => [
                    'purpose' => 'Opens an existing shop.',
                    'fields' => ['shop_id'],
                    'requirements' => 'shop_id must identify an existing shop. Preserve it when editing neighboring dialogue.',
                ],
                'hotel' => [
                    'purpose' => 'Opens an existing hotel.',
                    'fields' => ['hotel_id'],
                    'requirements' => 'hotel_id must identify an existing hotel.',
                ],
                'teleportation' => [
                    'purpose' => 'Teleports to an existing map or creates an instance of it.',
                    'action_data' => [
                        'teleportation' => [
                            'mapId' => 'existing map ID',
                            'x' => 'integer tile coordinate from 0 to map width - 1',
                            'y' => 'integer tile coordinate from 0 to map height - 1',
                            'createInstance' => 'boolean; clone the destination as a private instance',
                            'includeNpcs' => 'boolean; copy NPCs from the base map into the instance',
                            'scaleNpcsToPlayerLevel' => 'boolean; only meaningful for an instance with NPCs',
                            'npcLevelOffset' => 'integer added to the player level when scaling NPCs',
                            'scaleNpcLootItemLevels' => 'boolean; scale loot item levels in the instance',
                            'npcLootItemLevelOffset' => 'integer item-level offset',
                        ],
                    ],
                ],
                'randomizer' => [
                    'purpose' => 'Selects one direct outgoing edge by percentage.',
                    'requirements' => 'Put percentageChance in every outgoing edge.rules. Values must total 100; the editor treats the last edge as the remainder.',
                ],
                'profession' => [
                    'purpose' => 'Routes by the player profession.',
                    'options' => collect(Profession::cases())->mapWithKeys(fn (Profession $profession): array => [
                        $profession->value => $profession->description(),
                    ])->all(),
                    'requirements' => 'Provide exactly one option per profession, preferably keyed w, p, m, b, t and h. Each option may have at most one outgoing edge.',
                ],
                'minigame' => [
                    'purpose' => 'Starts a minigame and branches on its result.',
                    'action_data' => ['minigame' => ['type' => 'pipes|saper|mastermind|random', 'difficulty' => 'integer 1-3']],
                    'outputs' => ['source-success' => 'win', 'source-fail' => 'loss'],
                    'requirements' => 'Each result handle may have at most one edge.',
                ],
            ],
            'camera_focus' => [
                'placement' => 'special.action_data.focus',
                'npc' => ['type' => 'npc', 'npcId' => 'placed NPC ID', 'locationId' => 'that NPC location ID', 'mapId' => 'location map ID', 'x' => 'location x', 'y' => 'location y'],
                'coordinates' => ['type' => 'coordinates', 'x' => 'tile x on the current map', 'y' => 'tile y on the current map'],
                'reset' => ['type' => 'reset'],
                'runtime' => 'The client animates to the tile center in 260 ms with the target near 25% of screen height. npcId/locationId/mapId identify the editor selection; the client ultimately uses x/y.',
                'lifecycle' => 'Focus persists across subsequent nodes until another focus instruction, reset, or dialog close. Closing a dialog always resets it.',
                'selection' => 'For NPC focus, select only one of focus_targets returned by get_dialog_graph; those are placed NPC locations on maps where this dialog is used.',
            ],
            'rule_shape' => ['value' => 'required', 'value2' => 'rule-specific auxiliary value', 'consume' => 'optional boolean; true only for gold, honorPoints, items and dragonTears'],
            'rules' => [
                'gold' => ['value' => 'non-negative number', 'consume' => 'may deduct it'],
                'honorPoints' => ['value' => 'non-negative integer', 'consume' => 'may deduct it'],
                'level' => ['value' => 'minimum character level'],
                'levelBelow' => ['value' => 'character level must be lower than this'],
                'brotherhood' => ['value' => 0, 'meaning' => 'requires Karmazynowe Bractwo membership'],
                'items' => ['value' => 'array of BaseItem IDs or @item:key placeholders', 'value2' => 'parallel array of quantities, each 1-1000', 'consume' => 'may remove the quantities'],
                'equippedItems' => ['value' => 'non-empty unique BaseItem ID array; no two items may share a category'],
                'percentageChance' => ['value' => 'integer 0-100; primarily for randomizer edge.rules'],
                'questStep' => ['value' => 's-ID or q-ID, or an array; exact step means current step, whole quest means started'],
                'questBeforeStep' => ['value' => 's-ID or q-ID, or an array; exact step means earlier in the same quest, whole quest means not started'],
                'questAfterStep' => ['value' => 'prefer s-ID; passes on a later step. Whole-quest behavior is counterintuitive and treated by the engine like not started'],
                'dragonTears' => ['value' => 'non-negative number', 'consume' => 'may deduct it'],
                'messageContent' => ['value' => 'exact required player text, max 100 characters', 'meaning' => 'opens a text-input prompt in the client'],
                'dialogCounter' => ['value' => 'existing DialogCounter ID', 'value2' => "['>'|'='|'<', integer]"],
                'seasonalEvent' => ['value' => 'existing SeasonalEvent ID'],
                'timeAfter' => ['value' => 'HH:MM in 24-hour time'],
                'timeBefore' => ['value' => 'HH:MM in 24-hour time'],
                'weekday' => ['value' => 'non-empty array; 1=Monday through 7=Sunday'],
                'activePlayersOnMap' => ['value' => 'non-negative integer'],
                'hasActiveBlessing' => ['value' => true],
            ],
            'additional_actions' => [
                'timing' => 'The same object may be placed on a special node (runs when that speech is shown) or on an option (runs after the option is clicked).',
                'actions' => [
                    'addItems' => ['value' => 'BaseItem ID/@item:key array', 'value2' => 'parallel quantity array'],
                    'addGold' => ['value' => 'number'],
                    'addHonorPoints' => ['value' => 'number'],
                    'addExp' => ['value' => 'number'],
                    'addExpPercent' => ['value' => '0-100, max two decimal places'],
                    'setQuestStep' => ['value' => 'QuestStep ID or @step:quest-key:step-key'],
                    'blessing' => ['value' => 'existing BaseItem ID with category blessings', 'scale' => 'optional boolean'],
                    'setOutfit' => ['value' => 'existing outfit asset path only', 'duration' => 'minutes; 0 is permanent'],
                    'addDialogCounter' => ['value' => 'existing DialogCounter ID; increments it'],
                    'resetDialogCounter' => ['value' => 'existing DialogCounter ID'],
                    'resetAdditionalAttributePoints' => ['value' => 'number'],
                ],
            ],
            'option_additional_action' => [
                'timing' => 'A single enum action that runs when the option is clicked; independent from additional_actions.',
                'values' => [
                    'HEAL' => 'heal the character',
                    'SELF_KILL' => 'kill the character',
                    'SUBTRACT_EXP' => 'subtract experience',
                    'BATTLE' => 'start combat with the interacted NPC',
                    'KILL_AND_LOOT' => 'kill the NPC and show loot',
                    'KILL' => 'kill the NPC automatically',
                    'SHOW_MAIL' => 'open mail',
                    'SHOW_DEPOSIT' => 'open personal deposit',
                    'SHOW_CLAN_DEPOSIT' => 'open clan deposit',
                    'SHOW_AUCTIONS' => 'open auctions',
                ],
            ],
            'lookup_types' => [
                'shops', 'hotels', 'dialog_counters', 'seasonal_events', 'items', 'quests', 'dialogs', 'maps', 'npcs',
            ],
            'dialog_counter_scopes' => collect(DialogCounterScope::cases())->map(fn (DialogCounterScope $scope): string => $scope->value)->all(),
            'npc_placement' => [
                'base_npc_id' => 'must reference an existing BaseNPC; AI cannot create BaseNPC definitions or sprites',
                'locations' => 'one placed NPC may have one or more existing map locations, each with map_id, x and y',
                'dialog' => 'may reference an existing dialog_id or a dialog_key created earlier in the same change set',
                'enabled' => 'boolean visibility/availability flag',
                'auto_start_dialog' => 'boolean; starts the assigned dialog automatically when the player enters range',
                'auto_start_dialog_range' => 'positive tile range for automatic start',
            ],
            'supported_authoring' => [
                'quests and automatic kill/time/next-day progress',
                'dialog graph patches and all node/rule/action fields described above',
                'existing shop/hotel assignment inside dialog nodes',
                'camera focus using existing NPC locations or coordinates',
                'placed NPCs based on existing BaseNPC records',
                'BaseItems, shop inventory slots and BaseNPC loot membership',
            ],
            'not_exposed_for_ai_writes' => [
                'BaseNPC definitions, maps and graphic-dependent assets',
                'doors, hotels/rooms, dialog counters and seasonal-event creation',
                'books, audio, map tracks, respawn/spawn points and special attacks',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function dialogGraph(string $world, int $dialogId): array
    {
        $world = $this->worldService->use($world);
        $dialog = Dialog::query()
            ->with(['nodes.options', 'edges', 'npcs.base:id,name', 'npcs.locations.map:id,name'])
            ->findOrFail($dialogId);
        $dialogMapIds = $dialog->npcs
            ->flatMap(fn (Npc $npc) => $npc->locations->pluck('map_id'))
            ->filter()
            ->unique()
            ->values();
        $dialogMaps = GameMap::query()
            ->whereIn('id', $dialogMapIds)
            ->orderBy('name')
            ->get(['id', 'name', 'x', 'y']);
        $focusNpcs = $dialogMapIds->isEmpty()
            ? collect()
            : Npc::query()
                ->with(['base:id,name', 'locations' => fn ($query) => $query->whereIn('map_id', $dialogMapIds)->with('map:id,name,x,y')])
                ->whereHas('locations', fn ($query) => $query->whereIn('map_id', $dialogMapIds))
                ->orderBy('id')
                ->get();

        return [
            'world' => $world,
            'dialog' => [
                'id' => $dialog->id,
                'name' => $dialog->name,
                'shared_by_npcs' => $dialog->npcs->map(fn (Npc $npc): array => [
                    'npc_id' => $npc->id,
                    'base_npc_id' => $npc->base_npc_id,
                    'name' => $npc->base?->name,
                    'locations' => $npc->locations->map(fn ($location): array => [
                        'location_id' => $location->id,
                        'map_id' => $location->map_id,
                        'map_name' => $location->map?->name,
                        'x' => $location->x,
                        'y' => $location->y,
                    ])->values()->all(),
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
            'runtime_context' => [
                'maps' => $dialogMaps->map(fn (GameMap $map): array => [
                    'id' => $map->id,
                    'name' => $map->name,
                    'width' => $map->x,
                    'height' => $map->y,
                    'coordinate_bounds' => ['x' => [0, max(0, $map->x - 1)], 'y' => [0, max(0, $map->y - 1)]],
                ])->values()->all(),
                'focus_targets' => $focusNpcs->flatMap(fn (Npc $npc) => $npc->locations->map(fn ($location): array => [
                    'npc_id' => $npc->id,
                    'base_npc_id' => $npc->base_npc_id,
                    'name' => $npc->base?->name,
                    'location_id' => $location->id,
                    'map_id' => $location->map_id,
                    'map_name' => $location->map?->name,
                    'x' => $location->x,
                    'y' => $location->y,
                    'focus' => [
                        'type' => 'npc',
                        'npcId' => $npc->id,
                        'locationId' => $location->id,
                        'mapId' => $location->map_id,
                        'x' => $location->x,
                        'y' => $location->y,
                    ],
                ]))->values()->all(),
                'client_text' => [
                    'placeholders' => ['#nick', '#lvl', '#difflvl(N)'],
                    'bbcode' => ['b', 'i', 'u', 'href', 'img', 'br'],
                ],
            ],
            'editing_guidance' => [
                'Use patch_dialog for additions and targeted edits; do not replace the whole graph.',
                'A shared dialog is intentionally updated for every NPC listed in shared_by_npcs.',
                'Untouched shop_id and hotel_id values are preserved automatically.',
                'Node positions are recalculated after applying the patch to prevent overlap.',
                'Call get_dialog_capabilities for the exact semantics and shape of every node, rule and action.',
                'For NPC camera focus copy one exact focus object from runtime_context.focus_targets.',
                'Option rules control whether an answer is available; edge rules control which connected branch is selected.',
            ],
        ];
    }
}

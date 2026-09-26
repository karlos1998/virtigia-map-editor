<?php

namespace App\Mcp\Tools\Virtigia;

use App\Models\User;
use App\Services\Mcp\AiChangeSetService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Validate and save a previewable Virtigia content change set. This never changes game-world data. Supports map creation and collisions, BaseNPC creation from supplied graphics, directed transitions, quests, dialogs, placed NPCs, BaseItems, shops and loot.')]
class DraftChangeSetTool extends VirtigiaTool
{
    protected string $name = 'draft_change_set';

    public function __construct(
        private readonly AiChangeSetService $changeSetService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'prompt' => ['nullable', 'string', 'max:10000'],
            'operations' => ['required', 'array', 'min:1', 'max:30'],
        ]);

        try {
            /** @var User $user */
            $user = $request->user();
            $changeSet = $this->changeSetService->draft(
                $user,
                $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
                $validated['title'],
                $validated['prompt'] ?? null,
                $validated['operations'],
            );

            return Response::structured([
                'ok' => true,
                'requires_apply' => true,
                'change_set' => $this->changeSetService->present($changeSet),
            ]);
        } catch (ValidationException $exception) {
            return Response::structured(['ok' => false, 'errors' => $exception->errors()]);
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('World slug. Defaults to retro.'),
            'title' => $schema->string()->max(255)->description('Short commit-style summary.')->required(),
            'prompt' => $schema->string()->description('Original user request.'),
            'operations' => $schema->array()
                ->items($schema->object([
                    'type' => $schema->string()->enum([
                        'create_quest',
                        'patch_quest',
                        'create_dialog',
                        'replace_dialog',
                        'patch_dialog',
                        'assign_dialog_to_npc',
                        'place_npc',
                        'create_base_item',
                        'clone_base_item',
                        'update_base_item',
                        'attach_item_to_shop',
                        'attach_item_to_base_npc_loot',
                        'create_map',
                        'update_map_collisions',
                        'create_base_npc',
                        'create_map_transition',
                        'update_map_transition',
                        'delete_map_transition',
                    ])->required(),
                    'key' => $schema->string()->description('Temporary key for a created quest, dialog, BaseItem, map, or BaseNPC.'),
                    'dialog_id' => $schema->integer()->min(1),
                    'quest_id' => $schema->integer()->min(1)->description('Existing quest ID for patch_quest.'),
                    'dialog_key' => $schema->string(),
                    'npc_id' => $schema->integer()->min(1),
                    'base_npc_id' => $schema->integer()->min(1),
                    'base_npc_key' => $schema->string()->description('Temporary key of a BaseNPC created earlier in this change set.'),
                    'map_id' => $schema->integer()->min(1)->description('Existing map ID for update_map_collisions.'),
                    'map_key' => $schema->string()->description('Temporary key of a map created earlier in this change set.'),
                    'item_id' => $schema->integer()->min(1)->description('Existing BaseItem ID.'),
                    'item_key' => $schema->string()->description('Temporary key of a BaseItem created or cloned earlier in this change set.'),
                    'source_base_item_id' => $schema->integer()->min(1)->description('Existing BaseItem to clone.'),
                    'transition_id' => $schema->integer()->min(1)->description('Existing transition ID for update_map_transition or delete_map_transition.'),
                    'shop_id' => $schema->integer()->min(1),
                    'position' => $schema->integer()->min(0)->max(79)->description('Shop slot. position = row × 8 + column.'),
                    'row' => $schema->integer()->min(0)->max(9),
                    'column' => $schema->integer()->min(0)->max(7),
                    'enabled' => $schema->boolean(),
                    'auto_start_dialog' => $schema->boolean(),
                    'auto_start_dialog_range' => $schema->integer()->min(1),
                    'locations' => $schema->array()->items($schema->object([
                        'map_id' => $schema->integer()->min(1),
                        'map_key' => $schema->string(),
                        'x' => $schema->integer()->min(0)->required(),
                        'y' => $schema->integer()->min(0)->required(),
                    ])),
                    'data' => $schema->object([
                        'name' => $schema->string(),
                        'steps' => $schema->array()->items($schema->object([
                            'key' => $schema->string()->description('Required for a step in create_quest.'),
                            'step_id' => $schema->integer()->min(1)->description('Required for a step in patch_quest.'),
                            'name' => $schema->string(),
                            'description' => $schema->string()->nullable()->description('Player-facing text only; this never implements progression.'),
                            'visible_in_quest_list' => $schema->boolean(),
                            'auto_progress' => $schema->object([
                                'type' => $schema->string()->enum(['mobs', 'time'])->required(),
                                'time_seconds' => $schema->integer()->min(1)->description('Required only for type=time.'),
                                'mobs' => $schema->array()->items($schema->object([
                                    'type' => $schema->string()->enum(['base_npc', 'mob_species'])->required(),
                                    'base_npc_id' => $schema->integer()->min(1)->description('Required only for type=base_npc.'),
                                    'mob_species_id' => $schema->integer()->min(1)->description('Required only for type=mob_species.'),
                                    'quantity' => $schema->integer()->min(1)->required(),
                                ])->withoutAdditionalProperties())->description('Required only for type=mobs.'),
                            ])->withoutAdditionalProperties()->nullable()->description('Real automatic step progression. Use mobs for kill objectives and time for timed objectives.'),
                            'auto_advance_next_day' => $schema->boolean(),
                            'auto_advance_to_step_key' => $schema->string()->nullable()->description('create_quest only; requires auto_advance_next_day.'),
                            'auto_advance_to_step_id' => $schema->integer()->min(1)->nullable()->description('patch_quest only; requires auto_advance_next_day.'),
                        ])->withoutAdditionalProperties()),
                        'nodes' => $schema->array()->items($schema->object())->description('Dialog nodes; exact create/patch shapes are documented by the Virtigia Content Authoring skill.'),
                        'edges' => $schema->array()->items($schema->object())->description('Dialog edges.'),
                        'delete_node_ids' => $schema->array()->items($schema->integer()->min(1)),
                        'delete_option_ids' => $schema->array()->items($schema->integer()->min(1)),
                        'delete_edge_ids' => $schema->array()->items($schema->integer()->min(1)),
                        'category' => $schema->string(),
                        'rarity' => $schema->string(),
                        'price' => $schema->integer()->min(0),
                        'currency' => $schema->string(),
                        'specific_currency_price' => $schema->integer()->min(0)->nullable(),
                        'attributes' => $schema->object()->nullable(),
                        'attributes_patch' => $schema->object()->nullable(),
                        'remove_attributes' => $schema->array()->items($schema->string()),
                        'attribute_points' => $schema->object()->nullable(),
                        'manual_attribute_points' => $schema->object()->nullable(),
                        'reverse_attributes' => $schema->object()->nullable(),
                        'image_data_uri' => $schema->string(),
                        'collision' => $schema->string()->description('Full row-major collision bit string; exact length width_tiles × height_tiles.'),
                        'blocked_tiles' => $schema->array()->items($schema->object([
                            'x' => $schema->integer()->min(0)->required(),
                            'y' => $schema->integer()->min(0)->required(),
                        ])->withoutAdditionalProperties()),
                        'mode' => $schema->string()->enum(['replace', 'block', 'unblock']),
                        'tiles' => $schema->array()->items($schema->object([
                            'x' => $schema->integer()->min(0)->required(),
                            'y' => $schema->integer()->min(0)->required(),
                        ])->withoutAdditionalProperties()),
                        'level' => $schema->integer()->min(0),
                        'rank' => $schema->string()->enum(['NORMAL', 'ELITE', 'ELITE_II', 'ELITE_III', 'HERO', 'TITAN']),
                        'profession' => $schema->string()->enum(['w', 'p', 'm', 'b', 't', 'h']),
                        'type' => $schema->integer()->enum([0, 4])->description('0 = interactive blocking NPC/MOB; 4 = non-interactive non-blocking decorative layer.'),
                        'facing' => $schema->integer()->min(0)->max(3)->description('Initial direction: 0 south, 1 north, 2 west, 3 east.'),
                        'draw_offset_x' => $schema->integer()->min(-256)->max(256),
                        'draw_offset_y' => $schema->integer()->min(-256)->max(256),
                        'is_aggressive' => $schema->boolean(),
                        'divine_intervention' => $schema->boolean(),
                        'guaranteed_loot' => $schema->boolean(),
                        'min_respawn_time' => $schema->integer()->min(0)->nullable(),
                        'max_respawn_time' => $schema->integer()->min(0)->nullable(),
                        'source_map_id' => $schema->integer()->min(1),
                        'source_x' => $schema->integer()->min(0),
                        'source_y' => $schema->integer()->min(0),
                        'destination_map_id' => $schema->integer()->min(1),
                        'destination_x' => $schema->integer()->min(0),
                        'destination_y' => $schema->integer()->min(0),
                        'min_level' => $schema->integer()->min(0)->nullable(),
                        'max_level' => $schema->integer()->min(0)->nullable(),
                        'required_base_item_id' => $schema->integer()->min(1)->nullable(),
                        'required_base_item_key' => $schema->string()->nullable()->description('Temporary key of a BaseItem created earlier in this change set.'),
                    ])->description('Operation payload. New map: name + PNG/JPEG image_data_uri and optional collision/blocked_tiles. Collision patch: mode plus collision or tiles. New BaseNPC: name, image_data_uri, level, rank, category and optional rendering/behavior fields.'),
                ]))
                ->description('Ordered operations. Put create operations before operations that reference their temporary keys. Use @item:key inside dialog item actions.')
                ->required(),
        ];
    }
}

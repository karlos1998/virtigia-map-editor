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

#[Description('Validate and save a previewable Virtigia content change set. This never changes game-world data. Supports quests with real mob/time auto-progress, targeted quest/dialog patches, placed NPCs, BaseItem creation/cloning/editing, shop slots and BaseNPC loot assignments.')]
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
                    ])->required(),
                    'key' => $schema->string()->description('Temporary key for a created quest, dialog, or BaseItem.'),
                    'dialog_id' => $schema->integer()->min(1),
                    'quest_id' => $schema->integer()->min(1)->description('Existing quest ID for patch_quest.'),
                    'dialog_key' => $schema->string(),
                    'npc_id' => $schema->integer()->min(1),
                    'base_npc_id' => $schema->integer()->min(1),
                    'item_id' => $schema->integer()->min(1)->description('Existing BaseItem ID.'),
                    'item_key' => $schema->string()->description('Temporary key of a BaseItem created or cloned earlier in this change set.'),
                    'source_base_item_id' => $schema->integer()->min(1)->description('Existing BaseItem to clone.'),
                    'shop_id' => $schema->integer()->min(1),
                    'position' => $schema->integer()->min(0)->max(79)->description('Shop slot. position = row × 8 + column.'),
                    'row' => $schema->integer()->min(0)->max(9),
                    'column' => $schema->integer()->min(0)->max(7),
                    'enabled' => $schema->boolean(),
                    'auto_start_dialog' => $schema->boolean(),
                    'auto_start_dialog_range' => $schema->integer()->min(1),
                    'locations' => $schema->array()->items($schema->object([
                        'map_id' => $schema->integer()->min(1)->required(),
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
                    ])->description('Quest/dialog payload or BaseItem fields. For kill objectives, steps[].auto_progress is mandatory; text in description has no gameplay effect. For item edits prefer attributes_patch and remove_attributes; image_data_uri must be PNG/GIF 32×32.'),
                ]))
                ->description('Ordered operations. Put create operations before operations that reference their temporary keys. Use @item:key inside dialog item actions.')
                ->required(),
        ];
    }
}

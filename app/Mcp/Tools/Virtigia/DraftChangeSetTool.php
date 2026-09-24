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

#[Description('Validate and save a previewable Virtigia content change set. This never changes game-world data. Supports quests, targeted dialog patches, placed NPCs, BaseItem creation/cloning/editing, shop slots and BaseNPC loot assignments.')]
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
                    'data' => $schema->object()->description('Quest/dialog payload or BaseItem fields. For item edits prefer attributes_patch and remove_attributes; image_data_uri must be PNG/GIF 32×32.'),
                ]))
                ->description('Ordered operations. Put create operations before operations that reference their temporary keys. Use @item:key inside dialog item actions.')
                ->required(),
        ];
    }
}

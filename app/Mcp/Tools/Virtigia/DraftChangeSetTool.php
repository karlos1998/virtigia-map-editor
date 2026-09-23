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

#[Description('Validate and save a previewable Virtigia content change set. This never changes game-world data. Allowed operations are create_quest, create_dialog, replace_dialog, assign_dialog_to_npc, and place_npc with an existing BaseNPC.')]
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
                        'assign_dialog_to_npc',
                        'place_npc',
                    ])->required(),
                    'key' => $schema->string()->description('Temporary key for a created quest or dialog.'),
                    'dialog_id' => $schema->integer()->min(1),
                    'dialog_key' => $schema->string(),
                    'npc_id' => $schema->integer()->min(1),
                    'base_npc_id' => $schema->integer()->min(1),
                    'enabled' => $schema->boolean(),
                    'auto_start_dialog' => $schema->boolean(),
                    'auto_start_dialog_range' => $schema->integer()->min(1),
                    'locations' => $schema->array()->items($schema->object([
                        'map_id' => $schema->integer()->min(1)->required(),
                        'x' => $schema->integer()->min(0)->required(),
                        'y' => $schema->integer()->min(0)->required(),
                    ])),
                    'data' => $schema->object()->description('Quest or full dialog graph payload. Follow the installed skill schema.'),
                ]))
                ->description('Ordered operations. Put create_quest and create_dialog before operations that reference their keys.')
                ->required(),
        ];
    }
}

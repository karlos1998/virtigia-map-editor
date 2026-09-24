<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read a complete quest with ordered steps, mob/time auto-progress rules and next-day transitions. Call this after creating or before repairing a quest.')]
#[IsReadOnly]
class GetQuestTool extends VirtigiaTool
{
    protected string $name = 'get_quest';

    public function __construct(private readonly GameContentSearchService $searchService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'quest_id' => ['required', 'integer', 'min:1'],
        ]);

        return Response::structured($this->searchService->quest(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['quest_id'],
        ));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('World slug. Defaults to retro.'),
            'quest_id' => $schema->integer()->min(1)->description('Existing quest ID.')->required(),
        ];
    }
}

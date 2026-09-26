<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Inspect every incoming and outgoing map transition for one map, including exact coordinates, paired return passages, inclusive level bounds, required BaseItems, and hotel-room key behavior. Call this before creating, editing, or deleting a transition.')]
#[IsReadOnly]
class InspectMapTransitionsTool extends VirtigiaTool
{
    protected string $name = 'inspect_map_transitions';

    public function __construct(private readonly GameContentSearchService $searchService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'map_id' => ['required', 'integer', 'min:1'],
        ]);

        return Response::structured($this->searchService->mapTransitions(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['map_id'],
        ));
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
            'map_id' => $schema->integer()->min(1)->description('Existing map ID resolved with search_game_content.')->required(),
        ];
    }
}

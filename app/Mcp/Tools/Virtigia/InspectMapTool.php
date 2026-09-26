<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Inspect one map before editing it. Returns the image, tile/pixel dimensions, complete collision bit string, collision rows, blocked count, and exact row-major index formula. Call this before changing collisions.')]
#[IsReadOnly]
class InspectMapTool extends VirtigiaTool
{
    protected string $name = 'inspect_map';

    public function __construct(private readonly GameContentSearchService $searchService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'map_id' => ['required', 'integer', 'min:1'],
        ]);

        return Response::structured($this->searchService->map(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['map_id'],
        ));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('World slug. Defaults to retro.'),
            'map_id' => $schema->integer()->min(1)->description('Existing map ID resolved with search_game_content.')->required(),
        ];
    }
}

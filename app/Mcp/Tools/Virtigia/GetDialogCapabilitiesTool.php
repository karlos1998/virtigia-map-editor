<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Return the exact Virtigia dialog authoring model: node types, graph routing, camera focus, option and edge rules, rewards/actions, text formatting, counters, seasonal events, minigames, shops, hotels and teleport instances. Call before any non-trivial dialog draft.')]
#[IsReadOnly]
class GetDialogCapabilitiesTool extends VirtigiaTool
{
    protected string $name = 'get_dialog_capabilities';

    public function __construct(
        private readonly GameContentSearchService $searchService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
        ]);

        return Response::structured($this->searchService->dialogCapabilities(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
        ));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('World slug. Defaults to retro.'),
        ];
    }
}

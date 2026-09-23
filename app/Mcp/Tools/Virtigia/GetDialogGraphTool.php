<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one complete existing dialog graph, including stable node, option and edge IDs, shop/hotel references, and every NPC sharing it. Call this before patch_dialog.')]
#[IsReadOnly]
class GetDialogGraphTool extends VirtigiaTool
{
    protected string $name = 'get_dialog_graph';

    public function __construct(
        private readonly GameContentSearchService $searchService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'dialog_id' => ['required', 'integer', 'min:1'],
        ]);

        return Response::structured($this->searchService->dialogGraph(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['dialog_id'],
        ));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('World slug. Defaults to retro.'),
            'dialog_id' => $schema->integer()->min(1)->description('Existing dialog ID.')->required(),
        ];
    }
}

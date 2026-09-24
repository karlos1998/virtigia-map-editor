<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read one complete BaseItem with exact attributes, image, shop positions, BaseNPC loot sources, and allowed editing values. Call this before cloning or editing an item.')]
#[IsReadOnly]
class GetBaseItemTool extends VirtigiaTool
{
    protected string $name = 'get_base_item';

    public function __construct(private readonly GameContentSearchService $searchService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'base_item_id' => ['required', 'integer', 'min:1'],
        ]);

        return Response::structured($this->searchService->baseItem(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['base_item_id'],
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
            'base_item_id' => $schema->integer()->min(1)->description('Existing BaseItem ID.')->required(),
        ];
    }
}

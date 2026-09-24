<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read a shop inventory as a 10×8 grid with every occupied and free position. Call this before attaching an item to a shop.')]
#[IsReadOnly]
class GetShopInventoryTool extends VirtigiaTool
{
    protected string $name = 'get_shop_inventory';

    public function __construct(private readonly GameContentSearchService $searchService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'shop_id' => ['required', 'integer', 'min:1'],
        ]);

        return Response::structured($this->searchService->shopInventory(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['shop_id'],
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
            'shop_id' => $schema->integer()->min(1)->description('Existing shop ID.')->required(),
        ];
    }
}

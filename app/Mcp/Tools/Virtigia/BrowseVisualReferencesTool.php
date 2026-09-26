<?php

namespace App\Mcp\Tools\Virtigia;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemRarity;
use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Mcp\Responses\ImageResponse;
use App\Services\Mcp\VisualReferenceService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Browse actual Virtigia map, item or NPC graphics as MCP image blocks with exact source metadata. Use several references before analysing or creating visuals so decisions are grounded in the existing world style. This tool is read-only and does not train the model persistently.')]
#[IsReadOnly]
class BrowseVisualReferencesTool extends VirtigiaTool
{
    protected string $name = 'browse_visual_references';

    public function __construct(
        private readonly VisualReferenceService $visualReferenceService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'kind' => ['required', 'string', 'in:maps,items,npcs'],
            'query' => ['nullable', 'string', 'max:255'],
            'ids' => ['nullable', 'array', 'max:12'],
            'ids.*' => ['integer', 'min:1', 'distinct'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
            'category' => ['nullable', 'string', 'max:64'],
            'rarity' => ['nullable', 'string', 'max:64'],
            'rank' => ['nullable', 'string', 'max:64'],
            'min_level' => ['nullable', 'integer', 'min:0'],
            'max_level' => ['nullable', 'integer', 'min:0', 'gte:min_level'],
        ]);

        $result = $this->visualReferenceService->browse(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['kind'],
            $validated['query'] ?? null,
            $validated['ids'] ?? [],
            $validated['limit'] ?? ($validated['kind'] === 'maps' ? 6 : 12),
            [
                'category' => $validated['category'] ?? null,
                'rarity' => $validated['rarity'] ?? null,
                'rank' => $validated['rank'] ?? null,
                'min_level' => $validated['min_level'] ?? null,
                'max_level' => $validated['max_level'] ?? null,
            ],
        );

        $responses = [Response::json($result['metadata'])];

        foreach ($result['images'] as $image) {
            $responses[] = ImageResponse::fromBinary($image['binary'], $image['mime_type'])
                ->withMeta($image['meta']);
        }

        return Response::make($responses)->withStructuredContent($result['metadata']);
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('World slug. Defaults to retro.'),
            'kind' => $schema->string()->enum(['maps', 'items', 'npcs'])->description('Graphics family to browse.')->required(),
            'query' => $schema->string()->description('Optional case-insensitive name fragment.'),
            'ids' => $schema->array()->items($schema->integer()->min(1))
                ->description('Optional exact IDs. Results and images keep this order.'),
            'limit' => $schema->integer()->min(1)->max(12)->description('Maximum references. Maps are capped at 6; items and NPCs at 12.'),
            'category' => $schema->string()->enum(array_values(array_unique([
                ...BaseItemCategory::valuesToList(),
                ...BaseNpcCategory::valuesToList(),
            ])))->description('Optional item or NPC category filter.'),
            'rarity' => $schema->string()->enum(BaseItemRarity::valuesToList())->description('Optional item rarity filter.'),
            'rank' => $schema->string()->enum(BaseNpcRank::valuesToList())->description('Optional NPC rank filter.'),
            'min_level' => $schema->integer()->min(0)->description('Optional minimum NPC level.'),
            'max_level' => $schema->integer()->min(0)->description('Optional maximum NPC level.'),
        ];
    }
}

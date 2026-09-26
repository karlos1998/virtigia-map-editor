<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Search maps, placed NPCs, existing BaseNPC records, items, shops, hotels, quests, dialogs, dialog counters, seasonal events and mob species in one Virtigia world. Use this before drafting changes to resolve real IDs and disambiguate names.')]
#[IsReadOnly]
class SearchGameContentTool extends VirtigiaTool
{
    protected string $name = 'search_game_content';

    public function __construct(
        private readonly GameContentSearchService $searchService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'query' => ['required', 'string', 'max:255'],
            'types' => ['nullable', 'array'],
            'types.*' => ['string', 'in:maps,npcs,base_npcs,items,shops,hotels,quests,dialogs,dialog_counters,seasonal_events,mob_species'],
            'map_name' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:25'],
        ]);

        return Response::structured($this->searchService->search(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['query'],
            $validated['types'] ?? [],
            $validated['map_name'] ?? null,
            $validated['limit'] ?? 10,
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
            'query' => $schema->string()->description('Name or phrase to find.')->required(),
            'types' => $schema->array()
                ->items($schema->string()->enum(['maps', 'npcs', 'base_npcs', 'items', 'shops', 'hotels', 'quests', 'dialogs', 'dialog_counters', 'seasonal_events', 'mob_species']))
                ->description('Content types to search. Omit to search all types.'),
            'map_name' => $schema->string()->description('Optional map name filter for placed NPCs.'),
            'limit' => $schema->integer()->min(1)->max(25)->default(10),
        ];
    }
}

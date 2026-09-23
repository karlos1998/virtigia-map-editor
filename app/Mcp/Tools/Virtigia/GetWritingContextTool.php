<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\GameContentSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Return a small set of existing Virtigia dialog examples for tone, light dialect, option style, rules, and quest actions. Fetch this before writing new player-facing dialogue.')]
#[IsReadOnly]
class GetWritingContextTool extends VirtigiaTool
{
    protected string $name = 'get_writing_context';

    public function __construct(
        private readonly GameContentSearchService $searchService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'topic' => ['nullable', 'string', 'max:255'],
            'npc_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return Response::structured($this->searchService->writingContext(
            $validated['world'] ?? (string) config('services.virtigia_mcp.default_world', 'retro'),
            $validated['topic'] ?? null,
            $validated['npc_id'] ?? null,
            $validated['limit'] ?? 6,
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
            'topic' => $schema->string()->description('Optional quest subject, NPC name, or tone keyword.'),
            'npc_id' => $schema->integer()->min(1)->description('Optional placed NPC whose nearby dialogue style should be preferred.'),
            'limit' => $schema->integer()->min(1)->max(12)->default(6),
        ];
    }
}

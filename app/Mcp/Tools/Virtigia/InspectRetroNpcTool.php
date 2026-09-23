<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\RetroEngineAnalysisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Read the complete real Retro runtime combat profile of an existing BaseNPC, including engine multipliers, final battle stats, special attacks, loot table and current legendary chance.')]
#[IsReadOnly]
class InspectRetroNpcTool extends VirtigiaTool
{
    protected string $name = 'inspect_retro_npc';

    public function __construct(private readonly RetroEngineAnalysisService $analysisService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'base_npc_id' => ['required', 'integer', 'min:1'],
        ]);
        $this->analysisService->assertRetro($validated['world'] ?? 'retro');

        return Response::structured($this->analysisService->npc($validated['base_npc_id']));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('Must be retro.'),
            'base_npc_id' => $schema->integer()->min(1)->required(),
        ];
    }
}

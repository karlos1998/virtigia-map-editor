<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\RetroEngineAnalysisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Calculate the exact current Retro loot probabilities for a BaseNPC and estimate additional kills needed for 50% and 90% cumulative legendary-drop probability. Uses an in-memory tracker and never writes production data.')]
#[IsReadOnly]
class AnalyzeRetroLootTool extends VirtigiaTool
{
    protected string $name = 'analyze_retro_loot';

    public function __construct(private readonly RetroEngineAnalysisService $analysisService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'base_npc_id' => ['required', 'integer', 'min:1'],
            'kills_since_legendary' => ['nullable', 'integer', 'min:0'],
            'legendary_loot_bonus_percent' => ['nullable', 'numeric', 'min:0'],
            'heroic_loot_bonus_percent' => ['nullable', 'numeric', 'min:0'],
            'minimum_loot_chance_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);
        $this->analysisService->assertRetro($validated['world'] ?? 'retro');

        return Response::structured($this->analysisService->loot([
            'baseNpcId' => $validated['base_npc_id'],
            'killsSinceLegendary' => $validated['kills_since_legendary'] ?? 0,
            'legendaryLootChanceBonusPercent' => $validated['legendary_loot_bonus_percent'] ?? 0,
            'heroicLootChanceBonusPercent' => $validated['heroic_loot_bonus_percent'] ?? 0,
            'minimumLootChancePercent' => $validated['minimum_loot_chance_percent'] ?? 0,
        ]));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('Must be retro.'),
            'base_npc_id' => $schema->integer()->min(1)->required(),
            'kills_since_legendary' => $schema->integer()->min(0)->default(0),
            'legendary_loot_bonus_percent' => $schema->number()->min(0)->default(0),
            'heroic_loot_bonus_percent' => $schema->number()->min(0)->default(0),
            'minimum_loot_chance_percent' => $schema->integer()->min(0)->max(100)->default(0),
        ];
    }
}

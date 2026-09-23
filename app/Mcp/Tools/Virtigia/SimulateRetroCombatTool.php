<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\RetroEngineAnalysisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Build an in-memory fake Retro character from existing items and a legal skill allocation, then run a read-only Monte Carlo combat simulation against a real BaseNPC. This never writes to MongoDB.')]
#[IsReadOnly]
class SimulateRetroCombatTool extends VirtigiaTool
{
    protected string $name = 'simulate_retro_combat';

    public function __construct(private readonly RetroEngineAnalysisService $analysisService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'base_npc_id' => ['required', 'integer', 'min:1'],
            'level' => ['required', 'integer', 'min:1', 'max:300'],
            'profession' => ['required', 'string', 'in:p,w,t,h,m,b'],
            'base_item_ids' => ['nullable', 'array', 'max:20'],
            'base_item_ids.*' => ['integer', 'min:1'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['integer', 'min:1'],
            'active_skill_rotation' => ['nullable', 'array', 'max:20'],
            'active_skill_rotation.*' => ['string'],
            'additional_attributes' => ['nullable', 'array'],
            'additional_attributes.strength' => ['nullable', 'integer', 'min:0'],
            'additional_attributes.dexterity' => ['nullable', 'integer', 'min:0'],
            'additional_attributes.intelligence' => ['nullable', 'integer', 'min:0'],
            'iterations' => ['nullable', 'integer', 'min:100', 'max:10000'],
        ]);
        $this->analysisService->assertRetro($validated['world'] ?? 'retro');

        return Response::structured($this->analysisService->simulate([
            'baseNpcId' => $validated['base_npc_id'],
            'level' => $validated['level'],
            'profession' => $validated['profession'],
            'baseItemIds' => $validated['base_item_ids'] ?? [],
            'skills' => $validated['skills'] ?? [],
            'activeSkillRotation' => $validated['active_skill_rotation'] ?? [],
            'additionalAttributes' => [
                'strength' => data_get($validated, 'additional_attributes.strength', 0),
                'dexterity' => data_get($validated, 'additional_attributes.dexterity', 0),
                'intelligence' => data_get($validated, 'additional_attributes.intelligence', 0),
            ],
            'iterations' => $validated['iterations'] ?? 1000,
        ]));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('Must be retro.'),
            'base_npc_id' => $schema->integer()->min(1)->required(),
            'level' => $schema->integer()->min(1)->max(300)->required(),
            'profession' => $schema->string()->enum(['p', 'w', 't', 'h', 'm', 'b'])->required(),
            'base_item_ids' => $schema->array()->items($schema->integer()->min(1)),
            'skills' => $schema->object()->description('Map of exact skill enum ID to selected level.'),
            'active_skill_rotation' => $schema->array()->items($schema->string()),
            'additional_attributes' => $schema->object([
                'strength' => $schema->integer()->min(0),
                'dexterity' => $schema->integer()->min(0),
                'intelligence' => $schema->integer()->min(0),
            ]),
            'iterations' => $schema->integer()->min(100)->max(10000)->default(1000),
        ];
    }
}

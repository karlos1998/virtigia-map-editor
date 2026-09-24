<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\RetroBuildOptionsService;
use App\Services\Mcp\RetroEngineAnalysisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get real Retro equipment candidates with concrete loot/shop sources, availability flags and filters, plus the complete eligible skill tree and weapon rules. Use this before simulation; never recommend an item omitted by the requested availability filters.')]
#[IsReadOnly]
class GetRetroBuildOptionsTool extends VirtigiaTool
{
    protected string $name = 'get_retro_build_options';

    public function __construct(
        private readonly RetroEngineAnalysisService $analysisService,
        private readonly RetroBuildOptionsService $buildOptionsService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'level' => ['required', 'integer', 'min:1', 'max:300'],
            'profession' => ['required', 'string', 'in:p,w,t,h,m,b'],
            'equipment_per_category' => ['nullable', 'integer', 'min:1', 'max:30'],
            'max_rarity' => ['nullable', 'string', 'in:common,unique,heroic,upgraded,legendary,artefact'],
            'obtainable_only' => ['nullable', 'boolean'],
            'allowed_sources' => ['nullable', 'array'],
            'allowed_sources.*' => ['string', 'in:normal_mob,elite,elite_2,elite_3,hero,titan,gold_shop,currency_shop'],
            'exclude_event_sources' => ['nullable', 'boolean'],
            'exclude_admin_shops' => ['nullable', 'boolean'],
        ]);
        $this->analysisService->assertRetro($validated['world'] ?? 'retro');

        return Response::structured($this->buildOptionsService->get(
            $validated['world'] ?? 'retro',
            $validated['level'],
            $validated['profession'],
            $validated['equipment_per_category'] ?? 12,
            [
                'max_rarity' => $validated['max_rarity'] ?? null,
                'obtainable_only' => $validated['obtainable_only'] ?? true,
                'allowed_sources' => $validated['allowed_sources'] ?? [],
                'exclude_event_sources' => $validated['exclude_event_sources'] ?? false,
                'exclude_admin_shops' => $validated['exclude_admin_shops'] ?? true,
            ],
        ));
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('Must be retro.'),
            'level' => $schema->integer()->min(1)->max(300)->required(),
            'profession' => $schema->string()->enum(['p', 'w', 't', 'h', 'm', 'b'])->required(),
            'equipment_per_category' => $schema->integer()->min(1)->max(30)->default(12),
            'max_rarity' => $schema->string()->enum(['common', 'unique', 'heroic', 'upgraded', 'legendary', 'artefact'])
                ->description('Maximum allowed rarity. Example: heroic excludes legendary, upgraded and artefact items.'),
            'obtainable_only' => $schema->boolean()->default(true)
                ->description('Return only items with at least one concrete non-admin, non-test source.'),
            'allowed_sources' => $schema->array()->items($schema->string()->enum([
                'normal_mob', 'elite', 'elite_2', 'elite_3', 'hero', 'titan', 'gold_shop', 'currency_shop',
            ])),
            'exclude_event_sources' => $schema->boolean()->default(false),
            'exclude_admin_shops' => $schema->boolean()->default(true),
        ];
    }
}

<?php

namespace App\Mcp\Tools\Virtigia;

use App\Services\Mcp\RetroEngineAnalysisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Get real Retro equipment candidates and the complete eligible skill tree for a profession and level. Use this to design a legal fake build before combat simulation.')]
#[IsReadOnly]
class GetRetroBuildOptionsTool extends VirtigiaTool
{
    protected string $name = 'get_retro_build_options';

    public function __construct(private readonly RetroEngineAnalysisService $analysisService) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'level' => ['required', 'integer', 'min:1', 'max:300'],
            'profession' => ['required', 'string', 'in:p,w,t,h,m,b'],
            'equipment_per_category' => ['nullable', 'integer', 'min:1', 'max:30'],
        ]);
        $this->analysisService->assertRetro($validated['world'] ?? 'retro');

        return Response::structured([
            'equipment' => $this->analysisService->equipment(
                $validated['level'],
                $validated['profession'],
                $validated['equipment_per_category'] ?? 12,
            ),
            'skills' => $this->analysisService->skills($validated['level'], $validated['profession']),
        ]);
    }

    /** @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('Must be retro.'),
            'level' => $schema->integer()->min(1)->max(300)->required(),
            'profession' => $schema->string()->enum(['p', 'w', 't', 'h', 'm', 'b'])->required(),
            'equipment_per_category' => $schema->integer()->min(1)->max(30)->default(12),
        ];
    }
}

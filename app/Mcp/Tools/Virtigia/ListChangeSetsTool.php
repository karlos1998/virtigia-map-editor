<?php

namespace App\Mcp\Tools\Virtigia;

use App\Models\User;
use App\Services\Mcp\AiChangeSetService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('List AI change sets created by the connected employee, including draft, applied, and reverted status.')]
#[IsReadOnly]
class ListChangeSetsTool extends VirtigiaTool
{
    protected string $name = 'list_change_sets';

    public function __construct(
        private readonly AiChangeSetService $changeSetService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'world' => ['nullable', 'string'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        /** @var User $user */
        $user = $request->user();

        return Response::structured([
            'change_sets' => $this->changeSetService->list(
                $user,
                $validated['world'] ?? null,
                $validated['limit'] ?? 20,
            ),
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'world' => $schema->string()->description('Optional world slug filter.'),
            'limit' => $schema->integer()->min(1)->max(50)->default(20),
        ];
    }
}

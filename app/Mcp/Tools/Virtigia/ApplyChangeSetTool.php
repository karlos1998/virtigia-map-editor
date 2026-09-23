<?php

namespace App\Mcp\Tools\Virtigia;

use App\Models\User;
use App\Services\Mcp\AiChangeSetService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Apply a previously reviewed Virtigia AI change set. This writes game-world data and records complete snapshots for guarded rollback.')]
class ApplyChangeSetTool extends VirtigiaTool
{
    protected string $name = 'apply_change_set';

    public function __construct(
        private readonly AiChangeSetService $changeSetService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'change_set_id' => ['required', 'uuid'],
            'expected_revision' => ['required', 'integer', 'min:1'],
        ]);

        try {
            /** @var User $user */
            $user = $request->user();
            $changeSet = $this->changeSetService->apply(
                $user,
                $validated['change_set_id'],
                $validated['expected_revision'],
            );

            return Response::structured([
                'ok' => true,
                'change_set' => $this->changeSetService->present($changeSet),
            ]);
        } catch (ValidationException $exception) {
            return Response::structured(['ok' => false, 'errors' => $exception->errors()]);
        }
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'change_set_id' => $schema->string()->format('uuid')->required(),
            'expected_revision' => $schema->integer()->min(1)->description('Revision returned by draft_change_set.')->required(),
        ];
    }
}

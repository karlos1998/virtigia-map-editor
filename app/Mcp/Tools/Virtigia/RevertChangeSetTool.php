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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;

#[Description('Revert one applied Virtigia AI change set. The server refuses rollback if affected content was edited after that commit.')]
#[IsDestructive]
class RevertChangeSetTool extends VirtigiaTool
{
    protected string $name = 'revert_change_set';

    public function __construct(
        private readonly AiChangeSetService $changeSetService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'change_set_id' => ['required', 'uuid'],
        ]);

        try {
            /** @var User $user */
            $user = $request->user();
            $changeSet = $this->changeSetService->revert($user, $validated['change_set_id']);

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
        ];
    }
}

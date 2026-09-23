<?php

namespace App\Mcp\Tools\Virtigia;

use App\Models\User;
use App\Services\Mcp\McpWorldService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Return the connected Virtigia employee profile, available worlds, and the default world.')]
#[IsReadOnly]
class ProfileTool extends VirtigiaTool
{
    protected string $name = 'profile';

    public function __construct(
        private readonly McpWorldService $worldService,
    ) {}

    public function handle(Request $request): ResponseFactory
    {
        /** @var User $user */
        $user = $request->user();

        return Response::structured([
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'default_world' => (string) config('services.virtigia_mcp.default_world', 'retro'),
            'worlds' => $this->worldService->availableWorlds(),
        ]);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $tool = parent::toArray();
        $tool['_meta']['openai/profile'] = true;

        return $tool;
    }
}

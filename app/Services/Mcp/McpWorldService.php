<?php

namespace App\Services\Mcp;

use App\Models\DynamicModel;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Validation\ValidationException;

class McpWorldService
{
    public function __construct(
        private readonly WorldTemplateConnectionResolver $connectionResolver,
    ) {}

    public function use(?string $world): string
    {
        $world = strtolower(trim($world ?: (string) config('services.virtigia_mcp.default_world', 'retro')));
        $template = $this->connectionResolver->resolve($world);

        if ($template === null || ! $this->connectionResolver->registerTemplateConnection($template)) {
            throw ValidationException::withMessages([
                'world' => "Świat [{$world}] nie istnieje albo jest nieaktywny.",
            ]);
        }

        DynamicModel::setGlobalConnection($template->connection_name);

        return $template->slug;
    }

    /** @return array<int, array{value: string, label: string}> */
    public function availableWorlds(): array
    {
        return $this->connectionResolver->visibleOptions();
    }
}

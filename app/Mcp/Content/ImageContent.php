<?php

namespace App\Mcp\Content;

use Laravel\Mcp\Server\Concerns\HasMeta;
use Laravel\Mcp\Server\Contracts\Content;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;

class ImageContent implements Content
{
    use HasMeta;

    public function __construct(
        private readonly string $binary,
        private readonly string $mimeType,
    ) {}

    /** @return array<string, mixed> */
    public function toTool(Tool $tool): array
    {
        return $this->toArray();
    }

    /** @return array<string, mixed> */
    public function toPrompt(Prompt $prompt): array
    {
        return $this->toArray();
    }

    /** @return array<string, mixed> */
    public function toResource(Resource $resource): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return "[image {$this->mimeType}]";
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->mergeMeta([
            'type' => 'image',
            'data' => base64_encode($this->binary),
            'mimeType' => $this->mimeType,
        ]);
    }
}

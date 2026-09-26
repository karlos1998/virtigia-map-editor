<?php

namespace App\Mcp\Tools\Virtigia;

use Laravel\Mcp\Server\Tool;

abstract class VirtigiaTool extends Tool
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $tool = parent::toArray();
        $annotations = (array) ($tool['annotations'] ?? []);
        $tool['annotations'] = [
            'readOnlyHint' => false,
            'destructiveHint' => false,
            'openWorldHint' => false,
            ...$annotations,
        ];
        $tool['_meta']['securitySchemes'] = [
            [
                'type' => 'oauth2',
                'scopes' => ['mcp:use'],
            ],
        ];

        return $tool;
    }
}

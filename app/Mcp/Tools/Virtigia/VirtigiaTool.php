<?php

namespace App\Mcp\Tools\Virtigia;

use Laravel\Mcp\Server\Tool;

abstract class VirtigiaTool extends Tool
{
    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $tool = parent::toArray();
        $tool['_meta']['securitySchemes'] = [
            [
                'type' => 'oauth2',
                'scopes' => ['mcp:use'],
            ],
        ];

        return $tool;
    }
}

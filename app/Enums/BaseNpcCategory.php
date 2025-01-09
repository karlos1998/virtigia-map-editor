<?php

namespace App\Enums;

enum BaseNpcCategory: string
{
    case NPC = 'NPC';
    case MOB = 'MOB';

    public static function valuesToList(): array
    {
        return array_map(function ($case) {
            return $case->value;
        },
            self::cases()
        );
    }
}

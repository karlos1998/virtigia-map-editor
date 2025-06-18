<?php

namespace App\Enums;

enum WorldType: string
{
    case RETRO = 'retro';
    case CLASSIC = 'classic';
    case LEGACY = 'legacy';
    case DEMO = 'demo';

    public static function getAll(): array
    {
        return [
            self::RETRO->value,
            self::CLASSIC->value,
            self::LEGACY->value,
        ];
    }

    public static function getLabels(): array
    {
        return [
            self::RETRO->value => 'Retro',
            self::CLASSIC->value => 'Classic',
            self::LEGACY->value => 'Legacy',
            self::DEMO->value => 'Demo',
        ];
    }
}

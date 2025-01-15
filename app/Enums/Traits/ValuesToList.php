<?php

namespace App\Enums\Traits;

trait ValuesToList {
    public static function valuesToList(): array
    {
        return array_map(function ($case) {
            return $case->value;
        },
            self::cases()
        );
    }
}

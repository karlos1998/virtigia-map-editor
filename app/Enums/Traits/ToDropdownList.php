<?php

namespace App\Enums\Traits;
trait ToDropdownList {
    public static function toDropdownList(): array
    {
        $optionsList = [];

        foreach (self::cases() as $case) {
            $optionsList[] = [
                'label' => $case->description(),
                'value' => $case->value,
            ];
        }

        return $optionsList;
    }
}

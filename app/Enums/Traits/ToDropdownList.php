<?php
namespace App\Enums\Traits;

trait ToDropdownList {
    public static function toDropdownList(?callable $callback = null): array
    {
        $optionsList = [];

        foreach (self::cases() as $case) {
            $option = [
                'label' => $case->description(),
                'value' => $case->value,
            ];

            if ($callback) {
                $option = array_merge($option, $callback($case));
            }

            $optionsList[] = $option;
        }

        return $optionsList;
    }
}

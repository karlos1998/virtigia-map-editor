<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AssetPath implements ValidationRule
{
    public function __construct(private readonly bool $singleSegment = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::isValid($value) || ($this->singleSegment && str_contains($value, '/'))) {
            $fail('Podaj poprawną nazwę bez ukośników, segmentów „..” i znaków sterujących.');
        }
    }

    public static function isValid(string $path): bool
    {
        if ($path === '' || strlen($path) > 1024 || preg_match('/[\\\\\x00-\x1F\x7F]/u', $path)) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if (in_array($segment, ['', '.', '..'], true) || strlen($segment) > 255) {
                return false;
            }
        }

        return true;
    }
}

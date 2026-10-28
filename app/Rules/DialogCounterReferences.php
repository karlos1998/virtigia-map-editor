<?php

namespace App\Rules;

use App\Models\DialogCounter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DialogCounterReferences implements ValidationRule
{
    private const string REFERENCE_PATTERN = '/\[\[counter:([1-9][0-9]{0,18})\]\]/';

    public function __construct(private readonly DialogCounter $dialogCounter = new DialogCounter) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '[[counter:')) {
            return;
        }

        preg_match_all(self::REFERENCE_PATTERN, $value, $matches);

        if (str_contains(preg_replace(self::REFERENCE_PATTERN, '', $value), '[[counter:')) {
            $fail('Nieprawidłowy znacznik licznika. Użyj formatu [[counter:123]] z dodatnim ID licznika.');

            return;
        }

        $ids = [];
        foreach (array_unique($matches[1]) as $id) {
            $counterId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($counterId === false) {
                $fail('ID licznika w treści dialogu jest poza dozwolonym zakresem.');

                return;
            }
            $ids[] = $counterId;
        }

        $existingIds = $this->dialogCounter->newQuery()->whereIn('id', $ids)->pluck('id')->all();
        $missingIds = array_diff($ids, $existingIds);

        if ($missingIds !== []) {
            $fail('Liczniki wskazane w treści dialogu nie istnieją w tym świecie: '.implode(', ', $missingIds).'.');
        }
    }
}

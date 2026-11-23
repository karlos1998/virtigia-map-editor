<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DialogAnswerTimeout implements ValidationRule
{
    public function __construct(private Closure $options, private Closure $nodeType) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (($this->nodeType)() !== 'special') {
            $fail('Czas na odpowiedź można ustawić wyłącznie dla kwestii dialogowej.');

            return;
        }

        if (! is_array($value) || ! is_int($value['seconds'] ?? null) || $value['seconds'] < 1 || $value['seconds'] > 86400) {
            $fail('Czas na odpowiedź musi być liczbą całkowitą od 1 do 86400 sekund.');

            return;
        }

        if (! in_array($value['action'] ?? null, ['DEATH', 'RANDOM_OPTION', 'SELECT_OPTION', 'CLOSE_DIALOG'], true)) {
            $fail('Wybierz działanie po upływie czasu.');

            return;
        }

        if ($value['action'] !== 'SELECT_OPTION') {
            if (($value['optionId'] ?? null) !== null) {
                $fail('Konkretną odpowiedź można wskazać tylko dla działania wyboru odpowiedzi.');
            }

            return;
        }

        $optionId = $value['optionId'] ?? null;
        $option = collect(($this->options)())->first(fn ($option): bool => is_int($optionId) && (int) data_get($option, 'id') === $optionId);
        if (! $option) {
            $fail('Wybierz odpowiedź należącą do tej kwestii dialogowej.');

            return;
        }

        foreach (data_get($option, 'edges', []) as $edge) {
            if (array_key_exists('messageContent', data_get($edge, 'rules', []) ?? [])) {
                $fail('Automatyczna odpowiedź nie może wymagać wpisania tekstu.');

                return;
            }
        }
    }
}

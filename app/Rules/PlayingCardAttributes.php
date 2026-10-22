<?php

namespace App\Rules;

use App\Enums\PlayingCardAbility;
use App\Enums\PlayingCardKind;
use App\Enums\PlayingCardRow;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlayingCardAttributes implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Dane karty muszą być obiektem.');

            return;
        }

        $validator = Validator::make($value, [
            'definitionId' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'deck' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9][a-z0-9_-]*$/'],
            'kind' => ['required', Rule::enum(PlayingCardKind::class)],
            'strength' => ['required_if:kind,unit', 'nullable', 'integer', 'min:0', 'max:100'],
            'hero' => ['required', 'boolean'],
            'rows' => ['present', 'array', 'max:3'],
            'rows.*' => ['required', 'distinct', Rule::enum(PlayingCardRow::class)],
            'abilities' => ['present', 'array', 'max:10'],
            'abilities.*' => ['required', 'distinct', Rule::enum(PlayingCardAbility::class)],
            'bondGroup' => ['nullable', 'string', 'max:100'],
            'musterTargets' => ['sometimes', 'array', 'max:30'],
            'musterTargets.*' => ['string', 'max:100', 'distinct'],
            'avengerTarget' => ['nullable', 'string', 'max:100'],
            'generatedOnly' => ['sometimes', 'boolean'],
            'artwork' => ['sometimes', 'array:outfitSrc,backgroundSrc,illustrationSrc,frameX,frameY'],
            'artwork.outfitSrc' => ['nullable', 'string', 'max:2048', 'not_regex:/^\\s*(javascript|data):/i'],
            'artwork.backgroundSrc' => ['nullable', 'string', 'max:2048', 'not_regex:/^\\s*(javascript|data):/i'],
            'artwork.illustrationSrc' => ['nullable', 'string', 'max:2048', 'not_regex:/^\\s*(javascript|data):/i'],
            'artwork.frameX' => ['sometimes', 'integer', 'min:0', 'max:3'],
            'artwork.frameY' => ['sometimes', 'integer', 'min:0', 'max:3'],
        ]);

        foreach ($validator->errors()->all() as $message) {
            $fail($message);
        }

        if ($validator->fails()) {
            return;
        }

        $unit = $value['kind'] === PlayingCardKind::UNIT->value;
        $allowed = match ($value['kind']) {
            'unit' => ['spy', 'medic', 'bond', 'morale', 'horn', 'muster', 'scorch', 'scorch_c', 'avenger'],
            'special' => ['decoy', 'horn', 'scorch'],
            'weather' => ['frost', 'fog', 'rain', 'storm', 'clear'],
            'leader' => ['foltest_king', 'foltest_lord', 'foltest_siegemaster', 'foltest_son', 'foltest_steelforged'],
        };
        $specialHorn = $value['kind'] === 'special' && in_array('horn', $value['abilities'], true);
        if (array_diff($value['abilities'], $allowed) !== [] || (! $unit && ((! $specialHorn && $value['rows'] !== []) || count($value['abilities']) !== 1))) {
            $fail('Zdolności i rzędy muszą pasować do typu karty. Karty specjalne, pogoda i dowódcy mają jeden główny efekt.');
        }
        if ($unit && count($value['rows']) === 0) {
            $fail('Jednostka musi mieć przynajmniej jeden dozwolony rząd.');
        }

        if (! $unit && ($value['hero'] || ($value['strength'] ?? null) !== null)) {
            $fail('Tylko jednostka może być bohaterem i mieć siłę.');
        }

        if (in_array('bond', $value['abilities'], true) && empty($value['bondGroup'])) {
            $fail('Więź wymaga identyfikatora grupy.');
        }

        if (in_array('muster', $value['abilities'], true) && empty($value['musterTargets'])) {
            $fail('Braterstwo wymaga listy przywoływanych kart.');
        }

        if (in_array('avenger', $value['abilities'], true) && empty($value['avengerTarget'])) {
            $fail('Przemiana wymaga identyfikatora karty następczej.');
        }
    }
}

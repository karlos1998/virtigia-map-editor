<?php

namespace App\Http\Requests;

class UpdateMapCityRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'is_city' => ['required', 'boolean'],
            'related_map_ids' => ['sometimes', 'array', 'max:500'],
            'related_map_ids.*' => ['required', 'integer', 'distinct', $this->existsOnCurrentWorld('maps')],
        ];
    }

    public function messages(): array
    {
        return [
            'is_city.required' => 'Wybierz ustawienie wyczerpania.',
            'related_map_ids.max' => 'Jednorazowo można zmienić maksymalnie 500 lokalizacji.',
            'related_map_ids.*.exists' => 'Wybrana lokalizacja nie istnieje w bieżącym świecie.',
        ];
    }
}

<?php

namespace App\Http\Requests;

class BulkUpdateMapCityRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'is_city' => ['required', 'boolean'],
            'map_ids' => ['required', 'min:1', 'array', 'max:500'],
            'map_ids.*' => ['required', 'integer', 'distinct', $this->existsOnCurrentWorld('maps')],
        ];
    }

    public function messages(): array
    {
        return [
            'is_city.required' => 'Wybierz ustawienie wyczerpania.',
            'map_ids.max' => 'Jednorazowo można zmienić maksymalnie 500 lokalizacji.',
            'map_ids.*.exists' => 'Wybrana lokalizacja nie istnieje w bieżącym świecie.',
        ];
    }
}

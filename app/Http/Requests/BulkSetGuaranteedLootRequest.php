<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSetGuaranteedLootRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'level' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'level.required' => 'Podaj maksymalny level dla zmiany grupowej.',
            'level.integer' => 'Level musi być liczbą całkowitą.',
            'level.min' => 'Level nie może być mniejszy niż 1.',
            'level.max' => 'Level nie może być większy niż 1000.',
        ];
    }
}

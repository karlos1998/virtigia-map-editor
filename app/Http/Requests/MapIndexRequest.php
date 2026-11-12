<?php

namespace App\Http\Requests;

use App\Enums\MapGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MapIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'missing_battleground' => ['nullable', 'boolean'],
            'is_city' => ['nullable', 'boolean'],
            'map_group' => ['nullable', new Enum(MapGroup::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => 'Parametr per_page musi być liczbą.',
            'per_page.min' => 'Parametr per_page nie może być mniejszy niż 1.',
            'per_page.max' => 'Parametr per_page nie może być większy niż 100.',
        ];
    }

    /**
     * @return array{missing_battleground: bool, is_city: bool|null, map_group: string|null}
     */
    public function filters(): array
    {
        return [
            'missing_battleground' => $this->boolean('missing_battleground'),
            'is_city' => $this->filled('is_city') ? $this->boolean('is_city') : null,
            'map_group' => $this->filled('map_group') ? (string) $this->input('map_group') : null,
        ];
    }
}

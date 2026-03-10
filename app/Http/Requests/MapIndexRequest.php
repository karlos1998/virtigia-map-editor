<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
}

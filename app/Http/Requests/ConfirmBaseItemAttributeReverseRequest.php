<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmBaseItemAttributeReverseRequest extends FormRequest
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
            'preview_token' => ['required', 'uuid'],
        ];
    }

    public function previewToken(): string
    {
        return $this->validated('preview_token');
    }

    public function messages(): array
    {
        return ['preview_token.required' => 'Najpierw przelicz przedmiot.', 'preview_token.uuid' => 'Nieprawidłowy podgląd przeliczenia.'];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserApiTokenRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa tokenu jest wymagana.',
            'name.max' => 'Nazwa tokenu nie może być dłuższa niż 100 znaków.',
            'expires_at.date' => 'Data ważności musi być poprawną datą.',
            'expires_at.after' => 'Data ważności musi być w przyszłości.',
        ];
    }
}

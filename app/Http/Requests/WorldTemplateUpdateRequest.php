<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WorldTemplateUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAdministratorRole() ?? false;
    }

    public function rules(): array
    {
        return ['confirmed' => ['required', 'accepted']];
    }

    public function messages(): array
    {
        return ['confirmed.accepted' => 'Potwierdź rozpoczęcie operacji.', 'confirmed.required' => 'Potwierdź rozpoczęcie operacji.'];
    }
}

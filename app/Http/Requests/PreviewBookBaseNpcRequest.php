<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewBookBaseNpcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'min:1'],
            'rank' => ['nullable', 'string', 'max:32'],
            'lvl_from' => ['nullable', 'integer', 'min:1'],
            'lvl_to' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}


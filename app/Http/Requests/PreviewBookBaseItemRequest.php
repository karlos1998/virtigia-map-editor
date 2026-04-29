<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewBookBaseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_npc_ids' => ['nullable', 'array'],
            'base_npc_ids.*' => ['integer', 'min:1'],
            'rarity' => ['nullable', 'string', 'max:32'],
            'lvl_from' => ['nullable', 'integer', 'min:1'],
            'lvl_to' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}


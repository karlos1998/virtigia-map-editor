<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'is_daily' => ['nullable', 'boolean'],
            'has_steps' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', Rule::in(['id', 'name', 'created_at', 'updated_at', 'steps_count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}

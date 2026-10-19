<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorldTemplateUpdateChangesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAdministratorRole() ?? false;
    }

    public function rules(): array
    {
        return [
            'table' => ['nullable', 'string', Rule::in(config('world_templates.update_tables', []))],
            'type' => ['nullable', Rule::in(['added', 'updated', 'deleted'])],
            'search' => ['nullable', 'string', 'max:180'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}

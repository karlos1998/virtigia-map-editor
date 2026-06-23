<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorldTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAdministratorRole() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('world_templates', 'name')],
            'remote_database_server' => [
                'required',
                'string',
                Rule::in(array_keys(config('world_templates.remote_database_servers', []))),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Podaj nazwę template.',
            'name.unique' => 'Template o takiej nazwie już istnieje.',
            'remote_database_server.required' => 'Wybierz zdalną bazę.',
            'remote_database_server.in' => 'Wybrana zdalna baza nie istnieje w konfiguracji.',
        ];
    }
}

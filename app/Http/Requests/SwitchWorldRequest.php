<?php

namespace App\Http\Requests;

use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwitchWorldRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'world' => ['required', 'string', Rule::in(app(WorldTemplateConnectionResolver::class)->visibleSlugs())],
        ];
    }
}

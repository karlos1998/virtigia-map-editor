<?php

namespace App\Http\Requests;

use App\Enums\DialogCounterScope;
use App\Models\DialogCounter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateDialogCounterRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(DialogCounter::class, 'name')->ignore($this->route('dialogCounter')),
            ],
            'scope' => ['required', new Enum(DialogCounterScope::class)],
        ];
    }
}

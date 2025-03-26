<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeOptionAdditionalAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDialogNodeOptionRequest extends FormRequest
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
            'label' => [
                'required',
                'min:3',
                'max:250',
                'string',
            ],
            'additional_action' => [
                'nullable',
                new Enum(DialogNodeOptionAdditionalAction::class)
            ]
        ];
    }
}

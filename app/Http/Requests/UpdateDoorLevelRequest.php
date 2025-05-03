<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoorLevelRequest extends FormRequest
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
            'min_lvl' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'max_lvl' => [
                'nullable',
                'integer',
                'min:0',
                'gte:min_lvl',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMapWaterRequest extends FormRequest
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
            'water' => [
                'nullable',
                'string',
                // Water format is x1,x2,y,depth|x1,x2,y,depth|...
                // Each segment should match the pattern: numbers,numbers,numbers,numbers
                'regex:/^(\d+,\d+,\d+,[1-9](\|\d+,\d+,\d+,[1-9])*)$|^$/',
            ],
        ];
    }
}

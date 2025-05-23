<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDialogEdgeRequest extends FormRequest
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
            'sourceNodeIsInput' => [
                'required',
                'boolean',
            ],

            'sourceNodeId' => [
                'required',
                'integer',
            ],

            'sourceOptionId' => [
                'nullable',
                'required_if:sourceNodeIsInput,false',
                'integer',
            ],

            'targetNodeId' => [
                'required',
                'integer',
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Rules\DialogOptionRuleValidator;

class UpdateStartNodeEdgesRequest extends CurrentWorldRequest
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
            'edges' => [
                'array',
                'required',
            ],

            'edges.*.edge_id' => [
                'required',
                $this->existsOnCurrentWorld('dialog_edges'),
            ],

            'edges.*.rules' => [
                'nullable',
                'array',
                new DialogOptionRuleValidator,
            ],
        ];
    }
}

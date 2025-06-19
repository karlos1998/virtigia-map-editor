<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use App\Rules\DialogOptionRuleValidator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStartNodeEdgesRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

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
                "exists:$this->selectedDatabase.dialog_edges,id"
            ],

            'edges.*.rules' => [
                'nullable',
                'array',
                new DialogOptionRuleValidator(),
            ]
        ];
    }
}

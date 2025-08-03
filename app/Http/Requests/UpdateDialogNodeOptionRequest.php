<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeOptionAdditionalAction;
use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use App\Models\BaseItem;
use App\Rules\DialogOptionRuleValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\DialogNodeOptionRule;

class UpdateDialogNodeOptionRequest extends FormRequest
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
            'label' => [
                'required',
                'min:3',
                'max:1000',
                'string',
            ],
            'additional_action' => [
                'nullable',
                new Enum(DialogNodeOptionAdditionalAction::class)
            ],
            'rules' => [
                'nullable',
                'array',
                new DialogOptionRuleValidator()
            ],

            'edges' => [
                'array',
                'nullable',
            ],

            'edges.*.edge_id' => [
                'required',
                "exists:$this->selectedDatabase.dialog_edges,id"
                //todo - sprwadzac czy source_dialog_id to nasz dialog
            ],

            'edges.*.rules' => [
                'nullable',
                'array',
                new DialogOptionRuleValidator(),
            ]

        ];
    }

}

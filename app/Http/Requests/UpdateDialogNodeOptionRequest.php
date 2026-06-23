<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeOptionAdditionalAction;
use App\Rules\DialogNodeAdditionalActionsValidator;
use App\Rules\DialogOptionRuleValidator;
use Illuminate\Validation\Rules\Enum;

class UpdateDialogNodeOptionRequest extends CurrentWorldRequest
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
                'max:1000',
                'string',
            ],
            'additional_action' => [
                'nullable',
                new Enum(DialogNodeOptionAdditionalAction::class),
            ],
            'additional_actions' => [
                'nullable',
                'array',
                new DialogNodeAdditionalActionsValidator,
            ],
            'cooldown' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'rules' => [
                'nullable',
                'array',
                new DialogOptionRuleValidator,
            ],

            'edges' => [
                'array',
                'nullable',
            ],

            'edges.*.edge_id' => [
                'required',
                $this->existsOnCurrentWorld('dialog_edges'),
                // todo - sprwadzac czy source_dialog_id to nasz dialog
            ],

            'edges.*.rules' => [
                'nullable',
                'array',
                new DialogOptionRuleValidator,
            ],

        ];
    }
}

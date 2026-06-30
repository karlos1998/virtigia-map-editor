<?php

namespace App\Http\Requests;

use App\Rules\DialogNodeAdditionalActionsValidator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDialogNodeRequest extends FormRequest
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
            'content' => [
                'required',
                'min:3',
                'max:2000',
            ],

            'additional_actions' => [
                'nullable',
                'array',
                new DialogNodeAdditionalActionsValidator,
            ],

            'action_data' => [
                'nullable',
                'array',
            ],
            'action_data.focus' => [
                'nullable',
                'array',
            ],
            'action_data.focus.type' => [
                'required_with:action_data.focus',
                'in:npc,coordinates,reset',
            ],
            'action_data.focus.npcId' => [
                'nullable',
                'required_if:action_data.focus.type,npc',
                'integer',
            ],
            'action_data.focus.locationId' => [
                'nullable',
                'integer',
            ],
            'action_data.focus.mapId' => [
                'nullable',
                'integer',
            ],
            'action_data.focus.x' => [
                'nullable',
                'required_if:action_data.focus.type,npc,coordinates',
                'integer',
            ],
            'action_data.focus.y' => [
                'nullable',
                'required_if:action_data.focus.type,npc,coordinates',
                'integer',
            ],
        ];
    }
}

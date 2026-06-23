<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeOptionAdditionalAction;
use App\Rules\DialogNodeAdditionalActionsValidator;
use App\Rules\DialogOptionRuleValidator;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreDialogNodeFromJsonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'node' => ['required', 'array'],
            'node.type' => ['required', 'in:special,shop,hotel,teleportation,randomizer,profession,minigame'],
            'node.position' => ['required', 'array'],
            'node.position.x' => ['required', 'numeric'],
            'node.position.y' => ['required', 'numeric'],
            'node.content' => ['nullable', 'string', 'min:3', 'max:2000'],
            'node.additional_actions' => [
                'nullable',
                'array',
                new DialogNodeAdditionalActionsValidator,
            ],
            'node.action_data' => ['nullable', 'required_if:node.type,minigame', 'array'],
            'node.action_data.minigame' => ['required_if:node.type,minigame', 'array'],
            'node.action_data.minigame.type' => ['required_if:node.type,minigame', 'in:pipes,saper,mastermind,random'],
            'node.action_data.minigame.difficulty' => ['required_if:node.type,minigame', 'integer', 'min:1', 'max:3'],
            'options' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $nodeType = $this->input('node.type');

                    if ($nodeType === 'special' && (empty($value) || ! is_array($value))) {
                        $fail('Dla node typu special wymagane jest co najmniej jedno option.');
                    }
                },
            ],
            'options.*' => ['required', 'array'],
            'options.*.label' => ['required', 'string', 'min:3', 'max:1000'],
            'options.*.additional_action' => ['nullable', new Enum(DialogNodeOptionAdditionalAction::class)],
            'options.*.additional_actions' => ['nullable', 'array', new DialogNodeAdditionalActionsValidator],
            'options.*.cooldown' => ['nullable', 'integer', 'min:1'],
            'options.*.rules' => ['nullable', 'array', new DialogOptionRuleValidator],
        ];
    }
}

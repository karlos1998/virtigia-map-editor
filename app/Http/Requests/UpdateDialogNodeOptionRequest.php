<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeOptionAdditionalAction;
use App\Models\BaseItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\DialogNodeOptionRule;

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
            ],
            'rules' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    foreach ($value as $key => $ruleData) {
                        // Sprawdzenie, czy klucz istnieje w enumie
                        $enumRule = DialogNodeOptionRule::tryFrom($key);
                        if (!$enumRule) {
                            return $fail("Nieprawidłowy klucz rule: {$key}");
                        }

                        // Sprawdzenie poprawności wartości value
                        if (!isset($ruleData['value'])) {
                            return $fail("Brak wartości dla rule: {$key}");
                        }

                        if ($key === DialogNodeOptionRule::ITEMS->value) {
                            // Dla items: value musi być tablicą istniejących ID z BaseItem
                            if (!is_array($ruleData['value']) || !collect($ruleData['value'])->every(fn($v) => is_int($v))) {
                                return $fail("Dla rule: {$key}, wartość musi być tablicą liczb całkowitych.");
                            }

                            $invalidItems = collect($ruleData['value'])
                                ->filter(fn($id) => !BaseItem::where('id', $id)->exists());

                            if ($invalidItems->isNotEmpty()) {
                                return $fail("Dla rule: {$key}, następujące ID nie istnieją: " . $invalidItems->implode(', '));
                            }
                        } elseif (!is_numeric($ruleData['value'])) {
                            return $fail("Dla rule: {$key}, wartość musi być liczbą.");
                        }

                        // Sprawdzenie `consume`
                        if (isset($ruleData['consume'])) {
                            $canBeUsed = $enumRule->canBeUsed();
                            if (!$canBeUsed && $ruleData['consume'] !== false) {
                                return $fail("Dla rule: {$key}, consume musi być false lub nieobecne.");
                            }
                        }
                    }
                }
            ]
        ];
    }

}

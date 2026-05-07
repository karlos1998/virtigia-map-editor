<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeAdditionalAction;
use App\Enums\DialogNodeOptionAdditionalAction;
use App\Models\BaseItem;
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
            'node.type' => ['required', 'in:special,shop,hotel,teleportation,randomizer,profession'],
            'node.position' => ['required', 'array'],
            'node.position.x' => ['required', 'numeric'],
            'node.position.y' => ['required', 'numeric'],
            'node.content' => ['nullable', 'string', 'min:3', 'max:2000'],
            'node.additional_actions' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    foreach ($value as $key => $ruleData) {
                        $enumRule = DialogNodeAdditionalAction::tryFrom($key);
                        if (! $enumRule) {
                            $fail("Nieprawidłowy klucz rule: {$key}");

                            return;
                        }

                        if (! isset($ruleData['value'])) {
                            $fail("Brak wartości dla rule: {$key}");

                            return;
                        }

                        if ($key === DialogNodeAdditionalAction::ADD_ITEMS->value) {
                            if (! is_array($ruleData['value']) || ! collect($ruleData['value'])->every(fn ($itemValue) => is_int($itemValue))) {
                                $fail("Dla rule: {$key}, wartość musi być tablicą liczb całkowitych.");

                                return;
                            }

                            $invalidItems = collect($ruleData['value'])
                                ->filter(fn ($id) => ! BaseItem::where('id', $id)->exists());

                            if ($invalidItems->isNotEmpty()) {
                                $fail("Dla rule: {$key}, następujące ID nie istnieją: ".$invalidItems->implode(', '));

                                return;
                            }
                        } elseif ($key === DialogNodeAdditionalAction::SET_QUEST_STEP->value) {
                            if (! is_numeric($ruleData['value'])) {
                                $fail("Dla rule: {$key}, wartość musi być liczbą (ID kroku questa).");

                                return;
                            }

                            if (! \App\Models\QuestStep::where('id', $ruleData['value'])->exists()) {
                                $fail("Dla rule: {$key}, krok questa o ID {$ruleData['value']} nie istnieje.");

                                return;
                            }
                        } elseif ($key === DialogNodeAdditionalAction::BLESSING->value) {
                            $value = $ruleData['value'] ?? null;
                            if (! is_numeric($value)) {
                                $fail("Dla rule: {$key}, wartość musi być liczbą (ID przedmiotu).");

                                return;
                            }

                            $exists = BaseItem::where('id', $value)->where('category', 'blessings')->exists();
                            if (! $exists) {
                                $fail("Dla rule: {$key}, wybrany przedmiot nie istnieje lub nie jest błogosławieństwem.");

                                return;
                            }

                            if (array_key_exists('scale', $ruleData) && ! is_bool($ruleData['scale'])) {
                                $fail("Dla rule: {$key}, 'scale' musi być booleanem.");

                                return;
                            }
                        } elseif ($key === DialogNodeAdditionalAction::SET_OUTFIT->value) {
                            $value = $ruleData['value'] ?? null;
                            if (! is_string($value) || empty(trim($value))) {
                                $fail("Dla rule: {$key}, wartość musi być niepustym ciągiem znaków (ścieżka do grafiki).");

                                return;
                            }

                            if (! array_key_exists('duration', $ruleData) || ! is_numeric($ruleData['duration'])) {
                                $fail("Dla rule: {$key}, 'duration' musi być liczbą.");

                                return;
                            }

                            if ($ruleData['duration'] < 0) {
                                $fail("Dla rule: {$key}, 'duration' nie może być ujemna.");

                                return;
                            }
                        } elseif ($key === DialogNodeAdditionalAction::ADD_EXP_PERCENT->value) {
                            if (! is_numeric($ruleData['value'])) {
                                $fail("Dla rule: {$key}, wartość musi być liczbą.");

                                return;
                            }

                            $expPercentValue = (float) $ruleData['value'];
                            if ($expPercentValue < 0 || $expPercentValue > 100) {
                                $fail("Dla rule: {$key}, wartość musi być w zakresie 0-100.");

                                return;
                            }

                            if (! preg_match('/^\d+(\.\d{1,2})?$/', (string) $ruleData['value'])) {
                                $fail("Dla rule: {$key}, wartość może mieć maksymalnie 2 miejsca po przecinku.");

                                return;
                            }
                        } elseif (! is_numeric($ruleData['value'])) {
                            $fail("Dla rule: {$key}, wartość musi być liczbą.");

                            return;
                        }
                    }
                },
            ],
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
            'options.*.cooldown' => ['nullable', 'integer', 'min:1'],
            'options.*.rules' => ['nullable', 'array', new DialogOptionRuleValidator],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\DialogNodeAdditionalAction;
use App\Models\BaseItem;
use Closure;
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
                function(string $attribute, mixed $value, Closure $fail): void
                {
                    foreach ($value as $key => $ruleData) {
                        // Sprawdzenie, czy klucz istnieje w enumie
                        $enumRule = DialogNodeAdditionalAction::tryFrom($key);
                        if (!$enumRule) {
                            $fail("Nieprawidłowy klucz rule: {$key}");
                            return;
                        }

                        // Sprawdzenie poprawności wartości value
                        if (!isset($ruleData['value'])) {
                            $fail("Brak wartości dla rule: {$key}");
                            return;
                        }

                        if ($key === DialogNodeAdditionalAction::ADD_ITEMS->value) {
                            // Dla items: value musi być tablicą istniejących ID z BaseItem
                            if (!is_array($ruleData['value']) || !collect($ruleData['value'])->every(fn($v) => is_int($v))) {
                                $fail("Dla rule: {$key}, wartość musi być tablicą liczb całkowitych.");
                                return;
                            }

                            $invalidItems = collect($ruleData['value'])
                                ->filter(fn($id) => !BaseItem::where('id', $id)->exists());

                            if ($invalidItems->isNotEmpty()) {
                                $fail("Dla rule: {$key}, następujące ID nie istnieją: " . $invalidItems->implode(', '));
                                return;
                            }
                        } else if ($key === DialogNodeAdditionalAction::SET_QUEST_STEP->value) {
                            if (!is_numeric($ruleData['value'])) {
                                $fail("Dla rule: {$key}, wartość musi być liczbą (ID kroku questa).");
                                return;
                            }

                            // Check if the quest step exists
                            if (!\App\Models\QuestStep::where('id', $ruleData['value'])->exists()) {
                                $fail("Dla rule: {$key}, krok questa o ID {$ruleData['value']} nie istnieje.");
                                return;
                            }

                        } elseif ($key === \App\Enums\DialogNodeAdditionalAction::BLESSING->value) {
                            // For blessing action, value must be numeric and item must exist with category 'blessings'
                            $value = $ruleData['value'] ?? null;
                            if (!is_numeric($value)) {
                                $fail("Dla rule: {$key}, wartość musi być liczbą (ID przedmiotu).");
                                return;
                            }

                            $exists = \App\Models\BaseItem::where('id', $value)->where('category', 'blessings')->exists();
                            if (!$exists) {
                                $fail("Dla rule: {$key}, wybrany przedmiot nie istnieje lub nie jest błogosławieństwem.");
                                return;
                            }

                            // optional scale flag must be boolean if provided
                            if (array_key_exists('scale', $ruleData) && !is_bool($ruleData['scale'])) {
                                $fail("Dla rule: {$key}, 'scale' musi być booleanem.");
                                return;
                            }
                        } elseif ($key === \App\Enums\DialogNodeAdditionalAction::SET_OUTFIT->value) {
                            // For setOutfit action, value must be string (image path) and duration must be numeric
                            $value = $ruleData['value'] ?? null;
                            if (!is_string($value) || empty(trim($value))) {
                                $fail("Dla rule: {$key}, wartość musi być niepustym ciągiem znaków (ścieżka do grafiki).");
                                return;
                            }

                            // Check if duration is provided and is numeric
                            if (!array_key_exists('duration', $ruleData) || !is_numeric($ruleData['duration'])) {
                                $fail("Dla rule: {$key}, 'duration' musi być liczbą.");
                                return;
                            }

                            // Duration should be non-negative
                            if ($ruleData['duration'] < 0) {
                                $fail("Dla rule: {$key}, 'duration' nie może być ujemna.");
                                return;
                            }
                        } elseif (!is_numeric($ruleData['value'])) {
                            $fail("Dla rule: {$key}, wartość musi być liczbą.");
                            return;
                        }
                    }
                }
            ]
        ];
    }
}

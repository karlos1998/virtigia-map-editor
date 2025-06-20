<?php

namespace App\Rules;

use App\Enums\DialogNodeOptionRule;
use App\Models\BaseItem;
use App\Models\Quest;
use App\Models\QuestStep;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DialogOptionRuleValidator implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $key => $ruleData) {
            // Sprawdzenie, czy klucz istnieje w enumie
            $enumRule = DialogNodeOptionRule::tryFrom($key);
            if (!$enumRule) {
                $fail("Nieprawidłowy klucz rule: {$key}");
                return;
            }

            // Sprawdzenie poprawności wartości value
            if (!isset($ruleData['value'])) {
                $fail("Brak wartości dla rule: {$key}");
                return;
            }

            if ($key === DialogNodeOptionRule::ITEMS->value) {
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
            } elseif ($key === DialogNodeOptionRule::QUEST_STEP->value) {
                // Dla quest_step: value może być stringiem w formacie "s-{id}" lub tablicą takich stringów
                if (is_string($ruleData['value'])) {
                    // Legacy format: single string
                    if (!preg_match('/^(s|q)-\d+$/', $ruleData['value'])) {
                        $fail("Dla rule: {$key}, wartość musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                        return;
                    }

                    // Extract the step ID from the value
                    $stepId = (int) substr($ruleData['value'], 2);

                    // Check if the quest step exists
                    if (!\App\Models\QuestStep::where('id', $stepId)->exists()) {
                        $fail("Dla rule: {$key}, krok questa o ID {$stepId} nie istnieje.");
                        return;
                    }
                } elseif (is_array($ruleData['value'])) {
                    // New format: array of strings
                    foreach ($ruleData['value'] as $stepValue) {
                        if (!is_string($stepValue) || !preg_match('/^(s|q)-\d+$/', $stepValue)) {
                            $fail("Dla rule: {$key}, każda wartość w tablicy musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                            return;
                        }

                        // Extract the step ID from the value
                        $stepId = (int) substr($stepValue, 2);

                        // Check if the quest step exists
                        if (!\App\Models\QuestStep::where('id', $stepId)->exists()) {
                            $fail("Dla rule: {$key}, krok questa o ID {$stepId} nie istnieje.");
                            return;
                        }
                    }
                } else {
                    $fail("Dla rule: {$key}, wartość musi być stringiem lub tablicą stringów.");
                    return;
                }
            } elseif ($key === DialogNodeOptionRule::QUEST_BEFORE_STEP->value) {
                // Dla quest_before_step: value może być stringiem w formacie "s-{id}" lub tablicą takich stringów
                if (is_string($ruleData['value'])) {
                    // Legacy format: single string
                    if (!preg_match('/^(s|q)-\d+$/', $ruleData['value'])) {
                        $fail("Dla rule: {$key}, wartość musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                        return;
                    }

                    // Extract the step ID from the value
                    $stepId = (int) substr($ruleData['value'], 2);

                    // Check if the quest step exists
                    if (!\App\Models\QuestStep::where('id', $stepId)->exists()) {
                        $fail("Dla rule: {$key}, krok questa o ID {$stepId} nie istnieje.");
                        return;
                    }
                } elseif (is_array($ruleData['value'])) {
                    // New format: array of strings
                    foreach ($ruleData['value'] as $stepValue) {
                        if (!is_string($stepValue) || !preg_match('/^(s|q)-\d+$/', $stepValue)) {
                            $fail("Dla rule: {$key}, każda wartość w tablicy musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                            return;
                        }

                        // Extract the step ID from the value
                        $stepId = (int) substr($stepValue, 2);

                        // Check if the quest step exists
                        if (!\App\Models\QuestStep::where('id', $stepId)->exists()) {
                            $fail("Dla rule: {$key}, krok questa o ID {$stepId} nie istnieje.");
                            return;
                        }
                    }
                } else {
                    $fail("Dla rule: {$key}, wartość musi być stringiem lub tablicą stringów.");
                    return;
                }
            } elseif ($key === DialogNodeOptionRule::QUEST_AFTER_STEP->value) {
                // Dla quest_after_step: value może być stringiem w formacie "s-{id}" lub tablicą takich stringów
                if (is_string($ruleData['value'])) {
                    // Legacy format: single string
                    if (!preg_match('/^(s|q)-\d+$/', $ruleData['value'])) {
                        $fail("Dla rule: {$key}, wartość musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                        return;
                    }

                    // Extract the step ID from the value
                    $stepId = (int) substr($ruleData['value'], 2);

                    // Check if the quest step exists
                    if (!\App\Models\QuestStep::where('id', $stepId)->exists()) {
                        $fail("Dla rule: {$key}, krok questa o ID {$stepId} nie istnieje.");
                        return;
                    }
                } elseif (is_array($ruleData['value'])) {
                    // New format: array of strings
                    foreach ($ruleData['value'] as $stepValue) {
                        if (!is_string($stepValue) || !preg_match('/^(s|q)-\d+$/', $stepValue)) {
                            $fail("Dla rule: {$key}, każda wartość w tablicy musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                            return;
                        }

                        // Extract the step ID from the value
                        $stepId = (int) substr($stepValue, 2);

                        // Check if the quest step exists
                        if (!\App\Models\QuestStep::where('id', $stepId)->exists()) {
                            $fail("Dla rule: {$key}, krok questa o ID {$stepId} nie istnieje.");
                            return;
                        }
                    }
                } else {
                    $fail("Dla rule: {$key}, wartość musi być stringiem lub tablicą stringów.");
                    return;
                }
            } elseif (!is_numeric($ruleData['value'])) {
                $fail("Dla rule: {$key}, wartość musi być liczbą.");
                return;
            }

            // Sprawdzenie `consume`
            if (isset($ruleData['consume'])) {
                $canBeUsed = $enumRule->canBeUsed();
                if (!$canBeUsed && $ruleData['consume'] !== false) {
                    $fail("Dla rule: {$key}, consume musi być false lub nieobecne.");
                    return;
                }
            }
        }
    }
}

<?php

namespace App\Rules;

use App\Enums\DialogNodeOptionRule;
use App\Models\BaseItem;
use App\Models\DialogCounter;
use App\Models\Quest;
use App\Models\QuestStep;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DialogOptionRuleValidator implements ValidationRule
{
    /**
     * Validates a quest or quest step value.
     *
     * @param string $key The rule key
     * @param mixed $value The value to validate
     * @param \Closure $fail The fail callback
     * @return bool Whether validation passed
     */
    private function validateQuestOrQuestStep(string $key, mixed $value, Closure $fail): bool
    {
        if (is_string($value)) {
            // Single string format
            if (!preg_match('/^(s|q)-\d+$/', $value)) {
                $fail("Dla rule: {$key}, wartość musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                return false;
            }

            $prefix = substr($value, 0, 1);
            $id = (int) substr($value, 2);

            if ($prefix === 's') {
                // Check if the quest step exists
                if (!QuestStep::where('id', $id)->exists()) {
                    $fail("Dla rule: {$key}, krok questa o ID {$id} nie istnieje.");
                    return false;
                }
            } else if ($prefix === 'q') {
                // Check if the quest exists
                if (!Quest::where('id', $id)->exists()) {
                    $fail("Dla rule: {$key}, quest o ID {$id} nie istnieje.");
                    return false;
                }
            }
        } elseif (is_array($value)) {
            // Array of strings format
            foreach ($value as $itemValue) {
                if (!is_string($itemValue) || !preg_match('/^(s|q)-\d+$/', $itemValue)) {
                    $fail("Dla rule: {$key}, każda wartość w tablicy musi być stringiem w formacie \"s-{id}\" lub \"q-{id}\".");
                    return false;
                }

                $prefix = substr($itemValue, 0, 1);
                $id = (int) substr($itemValue, 2);

                if ($prefix === 's') {
                    // Check if the quest step exists
                    if (!QuestStep::where('id', $id)->exists()) {
                        $fail("Dla rule: {$key}, krok questa o ID {$id} nie istnieje.");
                        return false;
                    }
                } else if ($prefix === 'q') {
                    // Check if the quest exists
                    if (!Quest::where('id', $id)->exists()) {
                        $fail("Dla rule: {$key}, quest o ID {$id} nie istnieje.");
                        return false;
                    }
                }
            }
        } else {
            $fail("Dla rule: {$key}, wartość musi być stringiem lub tablicą stringów.");
            return false;
        }

        return true;
    }

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
            } elseif ($key === DialogNodeOptionRule::MESSAGE_CONTENT->value) {
                if (!is_string($ruleData['value']) || mb_strlen($ruleData['value']) > 100) {
                    $fail("Dla rule: {$key}, wartość musi być tekstem o długości max. 100 znaków.");
                    return;
                }
            } elseif ($key === DialogNodeOptionRule::DIALOG_COUNTER->value) {
                // Dla dialogCounter: value to ID licznika, value2 to tablica [operator, wartość]
                if (!is_int($ruleData['value']) || !DialogCounter::where('id', $ruleData['value'])->exists()) {
                    $fail("Dla rule: {$key}, wartość musi być istniejącym ID licznika dialogowego.");
                    return;
                }

                if (!isset($ruleData['value2']) || !is_array($ruleData['value2']) || count($ruleData['value2']) !== 2) {
                    $fail("Dla rule: {$key}, value2 musi być tablicą [operator, wartość].");
                    return;
                }

                [$operator, $counterValue] = $ruleData['value2'];

                if (!in_array($operator, ['>', '=', '<'], true)) {
                    $fail("Dla rule: {$key}, operator musi być jednym z: >, =, <.");
                    return;
                }

                if (!is_int($counterValue)) {
                    $fail("Dla rule: {$key}, wartość licznika musi być liczbą całkowitą.");
                    return;
                }
            } elseif (
                $key === DialogNodeOptionRule::QUEST_STEP->value ||
                $key === DialogNodeOptionRule::QUEST_BEFORE_STEP->value ||
                $key === DialogNodeOptionRule::QUEST_AFTER_STEP->value
            ) {
                // Dla quest_step, quest_before_step, quest_after_step: value może być stringiem w formacie "s-{id}" lub "q-{id}" lub tablicą takich stringów
                if (!$this->validateQuestOrQuestStep($key, $ruleData['value'], $fail)) {
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

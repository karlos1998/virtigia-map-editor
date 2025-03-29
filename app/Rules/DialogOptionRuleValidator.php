<?php

namespace App\Rules;

use App\Enums\DialogNodeOptionRule;
use App\Models\BaseItem;
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

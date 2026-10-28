<?php

namespace Tests\Unit;

use App\Enums\DialogNodeAdditionalAction;
use App\Rules\DialogNodeAdditionalActionsValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DialogNodeAdditionalActionsValidatorTest extends TestCase
{
    public function test_existing_counter_actions_without_an_amount_remain_valid(): void
    {
        self::assertSame([], $this->validateAction(['value' => 5]));
    }

    #[DataProvider('validAmounts')]
    public function test_counter_action_accepts_signed_integer_amounts(int $amount): void
    {
        self::assertSame([], $this->validateAction(['value' => 5, 'value2' => $amount]));
    }

    public static function validAmounts(): array
    {
        return [[1], [12], [0], [-7], [-2147483648], [2147483647]];
    }

    #[DataProvider('invalidAmounts')]
    public function test_counter_action_rejects_invalid_amounts(mixed $amount): void
    {
        self::assertNotSame([], $this->validateAction(['value' => 5, 'value2' => $amount]));
    }

    public static function invalidAmounts(): array
    {
        return [[null], [1.5], ['-2'], ['abc'], [true], [false], [[]], [[3]], [-2147483649], [2147483648]];
    }

    public function test_counter_action_still_requires_a_numeric_counter_id(): void
    {
        self::assertNotSame([], $this->validateAction(['value' => 'invalid', 'value2' => 5]));
        self::assertNotSame([], $this->validateAction(['value2' => 5]));
    }

    /**
     * @param  array<string, mixed>  $action
     * @return list<string>
     */
    private function validateAction(array $action): array
    {
        $errors = [];

        (new DialogNodeAdditionalActionsValidator)->validate('additional_actions', [
            DialogNodeAdditionalAction::ADD_DIALOG_COUNTER->value => $action,
        ], function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        return $errors;
    }
}

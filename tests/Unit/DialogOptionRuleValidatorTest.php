<?php

namespace Tests\Unit;

use App\Enums\DialogNodeOptionRule;
use App\Rules\DialogOptionRuleValidator;
use PHPUnit\Framework\TestCase;

class DialogOptionRuleValidatorTest extends TestCase
{
    public function test_it_accepts_time_weekday_map_player_count_and_blessing_rules(): void
    {
        self::assertSame([], $this->validateRules([
            DialogNodeOptionRule::TIME_AFTER->value => ['value' => '15:20', 'consume' => false],
            DialogNodeOptionRule::TIME_BEFORE->value => ['value' => '23:59', 'consume' => false],
            DialogNodeOptionRule::WEEKDAY->value => ['value' => [1, 7], 'consume' => false],
            DialogNodeOptionRule::ACTIVE_PLAYERS_ON_MAP->value => ['value' => 3, 'consume' => false],
            DialogNodeOptionRule::HAS_ACTIVE_BLESSING->value => ['value' => true, 'consume' => false],
            DialogNodeOptionRule::LEVEL_BELOW->value => ['value' => 40, 'consume' => false],
        ]));
    }

    public function test_it_rejects_invalid_time_rule_values(): void
    {
        self::assertNotSame([], $this->validateRules([
            DialogNodeOptionRule::TIME_AFTER->value => ['value' => '25:20', 'consume' => false],
        ]));
    }

    public function test_it_rejects_empty_or_out_of_range_weekday_values(): void
    {
        self::assertNotSame([], $this->validateRules([
            DialogNodeOptionRule::WEEKDAY->value => ['value' => [], 'consume' => false],
        ]));

        self::assertNotSame([], $this->validateRules([
            DialogNodeOptionRule::WEEKDAY->value => ['value' => [0, 8], 'consume' => false],
        ]));
    }

    public function test_it_rejects_invalid_map_player_count_and_blessing_values(): void
    {
        self::assertNotSame([], $this->validateRules([
            DialogNodeOptionRule::ACTIVE_PLAYERS_ON_MAP->value => ['value' => -1, 'consume' => false],
        ]));

        self::assertNotSame([], $this->validateRules([
            DialogNodeOptionRule::HAS_ACTIVE_BLESSING->value => ['value' => false, 'consume' => false],
        ]));
    }

    /**
     * @param  array<string, array<string, mixed>>  $rules
     * @return list<string>
     */
    private function validateRules(array $rules): array
    {
        $errors = [];

        (new DialogOptionRuleValidator)->validate('rules', $rules, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        return $errors;
    }
}

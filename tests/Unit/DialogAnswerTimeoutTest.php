<?php

namespace Tests\Unit;

use App\Http\Requests\StoreDialogNodeFromJsonRequest;
use App\Http\Requests\UpdateDialogNodeRequest;
use App\Models\Dialog;
use App\Models\DialogNode;
use App\Models\DialogNodeOption;
use App\Rules\DialogAnswerTimeout;
use App\Services\DialogService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DialogAnswerTimeoutTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[DataProvider('actions')]
    public function test_accepts_all_timeout_actions(string $action): void
    {
        self::assertSame([], $this->errors(['seconds' => 30, 'action' => $action, 'optionId' => $action === 'SELECT_OPTION' ? 7 : null]));
    }

    public static function actions(): array
    {
        return [['DEATH'], ['RANDOM_OPTION'], ['SELECT_OPTION'], ['CLOSE_DIALOG']];
    }

    #[DataProvider('invalidValues')]
    public function test_rejects_invalid_configuration(mixed $value): void
    {
        self::assertNotEmpty($this->errors($value));
    }

    public static function invalidValues(): array
    {
        return [
            [false], [[]], [['seconds' => 0, 'action' => 'DEATH']],
            [['seconds' => 86401, 'action' => 'DEATH']], [['seconds' => 1.5, 'action' => 'DEATH']],
            [['seconds' => 30, 'action' => 'unknown']], [['seconds' => 30, 'action' => 'SELECT_OPTION']],
            [['seconds' => 30, 'action' => 'SELECT_OPTION', 'optionId' => 999]],
            [['seconds' => 30, 'action' => 'DEATH', 'optionId' => 7]],
        ];
    }

    public function test_rejects_selected_option_that_requires_text(): void
    {
        self::assertNotEmpty($this->errors(
            ['seconds' => 30, 'action' => 'SELECT_OPTION', 'optionId' => 7],
            [['id' => 7, 'edges' => [['rules' => ['messageContent' => ['value' => 'tekst']]]]]],
        ));
    }

    public function test_rejects_timeout_on_automatic_nodes(): void
    {
        self::assertNotEmpty($this->errors(['seconds' => 30, 'action' => 'DEATH'], [], 'minigame'));
    }

    public function test_edit_and_json_import_validate_the_same_contract(): void
    {
        $editRules = (new UpdateDialogNodeRequest)->rules()['action_data.answerTimeout'];
        $importRules = (new StoreDialogNodeFromJsonRequest)->rules()['node.action_data.answerTimeout'];
        foreach ([$editRules, $importRules] as $rules) {
            self::assertContains('nullable', $rules);
            self::assertCount(1, array_filter($rules, fn ($rule): bool => $rule instanceof DialogAnswerTimeout));
        }
    }

    public function test_copying_a_node_remaps_the_selected_timeout_option(): void
    {
        $dialog = Mockery::mock(Dialog::class)->makePartial();
        $source = new DialogNode;
        $source->setRawAttributes([
            'type' => 'special', 'content' => 'Witaj', 'position' => '{"x":1,"y":2}',
            'action_data' => '{"answerTimeout":{"seconds":30,"action":"SELECT_OPTION","optionId":7}}',
        ]);
        $oldOption = new DialogNodeOption;
        $oldOption->setRawAttributes(['id' => 7, 'label' => 'Dalej']);
        $oldOption->setRelation('edges', new Collection);
        $source->setRelation('options', new Collection([$oldOption]));

        $copy = Mockery::mock(DialogNode::class)->makePartial();
        $copy->setRawAttributes(['type' => 'special', 'action_data' => json_encode($source->action_data)]);
        $newOption = new DialogNodeOption;
        $newOption->setRawAttributes(['id' => 107, 'label' => 'Dalej']);
        $options = Mockery::mock(HasMany::class);
        $options->shouldReceive('create')->once()->andReturn($newOption);
        $copy->shouldReceive('options')->once()->andReturn($options);
        $copy->shouldReceive('update')->once()->with(Mockery::on(fn (array $data): bool => $data['action_data']['answerTimeout']['optionId'] === 107))->andReturnTrue();
        $copy->shouldReceive('fresh')->once()->andReturnSelf();
        $nodes = Mockery::mock(HasMany::class);
        $nodes->shouldReceive('create')->once()->andReturn($copy);
        $dialog->shouldReceive('nodes')->once()->andReturn($nodes);

        self::assertSame($copy, (new DialogService($dialog))->copyNode($dialog, $source));
    }

    /** @return list<string> */
    private function errors(mixed $value, array $options = [['id' => 7, 'edges' => []]], string $nodeType = 'special'): array
    {
        $errors = [];
        (new DialogAnswerTimeout(fn (): array => $options, fn (): string => $nodeType))->validate('answerTimeout', $value, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        return $errors;
    }
}

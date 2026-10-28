<?php

namespace Tests\Unit;

use App\Http\Requests\StoreDialogNodeFromJsonRequest;
use App\Http\Requests\UpdateDialogNodeRequest;
use App\Models\DialogCounter;
use App\Models\DynamicModel;
use App\Rules\DialogCounterReferences;
use Illuminate\Database\Eloquent\Builder;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DialogCounterReferencesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_plain_text_and_other_markers_do_not_query_counters(): void
    {
        $counter = Mockery::mock(DialogCounter::class);
        $counter->shouldNotReceive('newQuery');

        self::assertSame([], $this->validateContent(new DialogCounterReferences($counter), '[b]Witaj #nick![/b] #lvl [[other:123]]'));
    }

    public function test_multiple_references_are_checked_in_one_query_without_duplicates(): void
    {
        $rule = $this->ruleWithExistingIds([123, 456], [123, 456]);

        self::assertSame([], $this->validateContent($rule, 'X: [[counter:123]], Y: [[counter:456]], X: [[counter:123]]'));
    }

    public function test_missing_counter_references_are_rejected(): void
    {
        $rule = $this->ruleWithExistingIds([123, 456], [123]);

        self::assertSame(['Liczniki wskazane w treści dialogu nie istnieją w tym świecie: 456.'], $this->validateContent($rule, 'X: [[counter:123]], Y: [[counter:456]]'));
    }

    #[DataProvider('invalidReferences')]
    public function test_invalid_counter_reference_syntax_and_ids_are_rejected_before_querying(string $reference): void
    {
        $counter = Mockery::mock(DialogCounter::class);
        $counter->shouldNotReceive('newQuery');

        self::assertNotSame([], $this->validateContent(new DialogCounterReferences($counter), $reference));
    }

    public static function invalidReferences(): array
    {
        return [
            ['[[counter:]]'], ['[[counter:0]]'], ['[[counter:-1]]'], ['[[counter:1.5]]'],
            ['[[counter:abc]]'], ['[[counter:123]'], ['[[counter:123'],
            ['[[counter:9223372036854775808]]'], ['[[counter:99999999999999999999]]'],
        ];
    }

    public function test_both_edit_and_json_import_requests_validate_counter_references(): void
    {
        foreach ([(new UpdateDialogNodeRequest)->rules()['content'], (new StoreDialogNodeFromJsonRequest)->rules()['node.content']] as $rules) {
            self::assertContains('string', $rules);
            self::assertCount(1, array_filter($rules, fn (mixed $rule): bool => $rule instanceof DialogCounterReferences));
        }
    }

    public function test_counter_model_tracks_the_selected_world(): void
    {
        $counter = new DialogCounter;

        try {
            DynamicModel::setGlobalConnection('world-one');
            self::assertSame('world-one', $counter->getConnectionName());
            DynamicModel::setGlobalConnection('world-two');
            self::assertSame('world-two', $counter->getConnectionName());
        } finally {
            DynamicModel::clearGlobalConnection();
        }
    }

    /**
     * @param  list<int>  $requestedIds
     * @param  list<int>  $existingIds
     */
    private function ruleWithExistingIds(array $requestedIds, array $existingIds): DialogCounterReferences
    {
        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('whereIn')->once()->with('id', $requestedIds)->andReturnSelf();
        $query->shouldReceive('pluck')->once()->with('id')->andReturn(collect($existingIds));
        $counter = Mockery::mock(DialogCounter::class);
        $counter->shouldReceive('newQuery')->once()->andReturn($query);

        return new DialogCounterReferences($counter);
    }

    /** @return list<string> */
    private function validateContent(DialogCounterReferences $rule, string $content): array
    {
        $errors = [];
        $rule->validate('content', $content, function (string $message) use (&$errors): void {
            $errors[] = $message;
        });

        return $errors;
    }
}

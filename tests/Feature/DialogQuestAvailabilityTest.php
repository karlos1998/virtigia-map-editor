<?php

namespace Tests\Feature;

use App\Services\DialogQuestAvailabilityService;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DialogQuestAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DB::connection();
        $schema = $this->connection->getSchemaBuilder();
        $schema->create('dialogs', static fn (Blueprint $table) => $table->id());
        $schema->create('dialog_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_dialog_id')->constrained('dialogs')->cascadeOnDelete();
            $table->string('type');
            $table->json('additional_actions')->nullable();
        });
        $schema->create('dialog_node_options', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('node_id');
            $table->string('label')->nullable();
            $table->json('rules')->nullable();
            $table->string('additional_action')->nullable();
            $table->json('additional_actions')->nullable();
        });
        $schema->create('dialog_edges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('source_dialog_id');
            $table->unsignedBigInteger('source_node_id')->nullable();
            $table->unsignedBigInteger('source_option_id')->nullable();
            $table->unsignedBigInteger('target_node_id');
            $table->string('source_handle')->nullable();
            $table->json('rules')->nullable();
        });
        $schema->create('quests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        $schema->create('quest_steps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('quest_id');
            $table->boolean('visible_in_quest_list');
        });

        (require database_path('migrations/remote/2026_11_24_114843_create_dialog_quest_availability_table.php'))->up();
    }

    protected function tearDown(): void
    {
        $this->connection->getSchemaBuilder()->drop('dialog_quest_availability');
        foreach (['dialog_edges', 'dialog_node_options', 'dialog_nodes', 'dialogs', 'quest_steps', 'quests'] as $table) {
            $this->connection->getSchemaBuilder()->drop($table);
        }
        parent::tearDown();
    }

    public function test_it_compiles_alternative_routes_and_only_refreshes_changed_dialogs(): void
    {
        DB::table('dialogs')->insert([['id' => 1], ['id' => 2]]);
        DB::table('quests')->insert(['id' => 10, 'name' => 'Próba']);
        DB::table('quest_steps')->insert(['id' => 100, 'quest_id' => 10, 'visible_in_quest_list' => true]);
        $this->node(11, 1, 'start');
        $this->node(12, 1, 'special');
        $this->node(13, 1, 'special', $this->action(100));
        $this->node(21, 2, 'start');
        $this->edge(1, 12, sourceNode: 11, rules: ['level' => ['value' => 20]]);
        DB::table('dialog_node_options')->insert([
            ['id' => 50, 'node_id' => 12, 'rules' => json_encode(['items' => ['value' => 80]]), 'additional_actions' => $this->action('s:100')],
            ['id' => 51, 'node_id' => 12, 'rules' => null, 'additional_actions' => null],
        ]);
        $this->edge(1, 13, sourceOption: 51, rules: ['questStep' => ['value' => 's:91']]);

        $service = app(DialogQuestAvailabilityService::class);
        $this->assertSame(['status' => 'pending', 'reason' => null, 'quests' => []], $service->inspect($this->connection, 1));
        $this->assertSame(2, $service->refresh($this->connection));
        $this->assertSame([
            ['questId' => 10, 'conditions' => [
                ['check' => ['rules' => ['level' => ['value' => 20]]]],
                ['optionId' => 50, 'check' => ['rules' => ['items' => ['value' => 80]]]],
            ]],
            ['questId' => 10, 'conditions' => [
                ['check' => ['rules' => ['level' => ['value' => 20]]]],
                ['optionId' => 51, 'check' => ['rules' => []]],
                ['check' => ['rules' => ['questStep' => ['value' => 's:91']]]],
            ]],
        ], $this->requirements(1));
        $this->assertSame([
            'status' => 'ready',
            'reason' => null,
            'quests' => [[
                'id' => 10, 'name' => 'Próba',
                'paths' => array_column($this->requirements(1), 'conditions'),
            ]],
        ], $service->inspect($this->connection, 1));
        $this->assertSame([], $this->requirements(2));
        $this->assertSame(['status' => 'ready', 'reason' => null, 'quests' => []], $service->inspect($this->connection, 2));
        $this->assertSame(0, $service->refresh($this->connection));

        DB::table('dialog_edges')->where('source_option_id', 51)->delete();
        $this->assertSame(1, $service->refresh($this->connection));
        $this->assertCount(1, $this->requirements(1));

        DB::table('quest_steps')->where('id', 100)->update(['visible_in_quest_list' => false]);
        $this->assertSame(1, $service->refresh($this->connection));
        $this->assertSame([], $this->requirements(1));
        $this->assertSame(0, $service->refresh($this->connection));
    }

    public function test_it_records_unsupported_dialogs_and_removes_deleted_dialogs(): void
    {
        DB::table('dialogs')->insert(['id' => 1]);
        $this->questSteps(100);
        $this->node(11, 1, 'start');
        $this->node(12, 1, 'special', $this->action(100));
        $this->edge(1, 12, sourceNode: 11, rules: ['futureRule' => ['value' => 1]]);

        $service = app(DialogQuestAvailabilityService::class);
        $this->assertSame(1, $service->refresh($this->connection));
        $row = DB::table(DialogQuestAvailabilityService::TABLE)->first();
        $this->assertNull($row->requirements);
        $this->assertSame('rule:futureRule', $row->fallback_reason);
        $this->assertSame(['status' => 'uncompiled', 'reason' => 'rule:futureRule', 'quests' => []], $service->inspect($this->connection, 1));
        $this->assertSame(0, $service->refresh($this->connection));

        DB::table('dialogs')->where('id', 1)->delete();
        $service->refresh($this->connection);
        $this->assertDatabaseCount(DialogQuestAvailabilityService::TABLE, 0);
    }

    public function test_local_dialog_without_quest_actions_compiles_even_with_unknown_rules(): void
    {
        DB::table('dialogs')->insert(['id' => 1]);
        $this->node(11, 1, 'start');
        $this->node(12, 1, 'special');
        $this->edge(1, 12, sourceNode: 11, rules: ['futureRule' => ['value' => 1]]);

        $service = app(DialogQuestAvailabilityService::class);
        $this->assertSame(1, $service->refresh($this->connection));
        $this->assertSame(['status' => 'ready', 'reason' => null, 'quests' => []], $service->inspect($this->connection, 1));
    }

    public function test_profession_uses_first_matching_option_and_first_passing_edge(): void
    {
        DB::table('dialogs')->insert(['id' => 1]);
        $this->questSteps(100, 101);
        $this->node(11, 1, 'start');
        $this->node(12, 1, 'profession', $this->action(101));
        $this->node(13, 1, 'special');
        $this->node(14, 1, 'special', $this->action(100));
        $this->node(15, 1, 'special', $this->action(101));

        $this->edge(1, 12, sourceNode: 11);
        DB::table('dialog_node_options')->insert([
            ['id' => 20, 'node_id' => 12, 'label' => ' W - Wojownik ', 'rules' => '{"level":{"value":99}}'],
            ['id' => 21, 'node_id' => 12, 'label' => 'w - druga opcja', 'rules' => null],
            ['id' => 22, 'node_id' => 12, 'label' => 'm - Mag', 'rules' => null],
        ]);
        $this->edge(1, 14, sourceOption: 20, rules: ['level' => ['value' => 10]]);
        $this->edge(1, 13, sourceOption: 20, rules: ['level' => ['value' => 30], 'gold' => ['value' => 1]]);
        $this->edge(1, 15, sourceOption: 21);
        $this->edge(1, 15, sourceOption: 22);

        $service = app(DialogQuestAvailabilityService::class);
        $this->assertSame(1, $service->refresh($this->connection));
        $paths = array_column($this->requirements(1), 'conditions', 'questId');
        $this->assertCount(2, $paths);
        $this->assertSame([[
            'profession' => 'w',
            'check' => ['rules' => ['level' => ['value' => 10]]],
            'unless' => [['rules' => ['level' => ['value' => 30], 'gold' => ['value' => 1]]]],
        ]], $paths[100]);
        $this->assertSame('m', $paths[101][0]['profession']);
        $this->assertSame(0, $service->refresh($this->connection));

        DB::table('dialog_node_options')->where('id', 20)->update(['label' => 'p - Paladyn']);
        $this->assertSame(1, $service->refresh($this->connection));
        $this->assertEqualsCanonicalizing(['w', 'm', 'p'], array_map(fn (array $path): string => $path['conditions'][0]['profession'], $this->requirements(1)));
    }

    public function test_randomizer_uses_positive_weights_only_when_a_weighted_edge_is_eligible(): void
    {
        DB::table('dialogs')->insert(['id' => 1]);
        $this->questSteps(100, 101, 102);
        $this->node(11, 1, 'start');
        $this->node(12, 1, 'randomizer');
        $this->node(13, 1, 'special');
        $this->node(14, 1, 'special', $this->action(100));
        $this->node(15, 1, 'special', $this->action(101));
        $this->node(16, 1, 'special', $this->action(102));
        $this->edge(1, 12, sourceNode: 11);
        $this->edge(1, 13, sourceNode: 12, rules: ['percentageChance' => ['value' => 5], 'level' => ['value' => 20]]);
        $this->edge(1, 14, sourceNode: 12, rules: ['percentageChance' => ['value' => 1], 'gold' => ['value' => 1]]);
        $this->edge(1, 15, sourceNode: 12, rules: ['percentageChance' => ['value' => 0]]);
        $this->edge(1, 16, sourceNode: 12, rules: ['percentageChance' => ['value' => '5']]);

        $service = app(DialogQuestAvailabilityService::class);
        $this->assertSame(1, $service->refresh($this->connection));
        $paths = array_column($this->requirements(1), 'conditions', 'questId');
        $this->assertSame([['check' => ['rules' => ['gold' => ['value' => 1]]]]], $paths[100]);
        $this->assertSame([[
            'check' => ['rules' => []],
            'unless' => [['rules' => ['level' => ['value' => 20]]], ['rules' => ['gold' => ['value' => 1]]]],
        ]], $paths[101]);
        $this->assertSame($paths[101], $paths[102]);
        $this->assertSame(0, $service->refresh($this->connection));

        DB::table('dialog_edges')->where('source_node_id', 12)->where('target_node_id', 13)->delete();
        $this->assertSame(1, $service->refresh($this->connection));
        $paths = array_column($this->requirements(1), 'conditions', 'questId');
        $this->assertCount(1, $paths[101][0]['unless']);

        $this->edge(1, 13, sourceNode: 12, rules: ['percentageChance' => ['value' => 1]]);
        $this->assertSame(1, $service->refresh($this->connection));
        $this->assertSame([100], array_column($this->requirements(1), 'questId'));
    }

    public function test_minigame_checks_both_outcomes_and_ignores_its_own_quest_action(): void
    {
        DB::table('dialogs')->insert(['id' => 1]);
        $this->questSteps(100, 101, 102);
        $this->node(11, 1, 'start');
        $this->node(12, 1, 'minigame', $this->action(102));
        $this->node(13, 1, 'special', $this->action(100));
        $this->node(14, 1, 'special', $this->action(101));
        $this->edge(1, 12, sourceNode: 11);
        $this->edge(1, 13, sourceNode: 12, rules: ['level' => ['value' => 20]]);
        $this->edge(1, 14, sourceNode: 12, rules: ['percentageChance' => ['value' => 40]]);
        DB::table('dialog_edges')->where('target_node_id', 13)->update(['source_handle' => 'source-success']);
        DB::table('dialog_edges')->where('target_node_id', 14)->update(['source_handle' => 'source-fail']);

        $this->assertSame(1, app(DialogQuestAvailabilityService::class)->refresh($this->connection));
        $paths = array_column($this->requirements(1), 'conditions', 'questId');
        $this->assertCount(2, $paths);
        $this->assertSame([['check' => ['rules' => ['level' => ['value' => 20]]]]], $paths[100]);
        $this->assertSame([['check' => ['rules' => ['percentageChance' => ['value' => 40]]]]], $paths[101]);
    }

    private function questSteps(int ...$ids): void
    {
        DB::table('quest_steps')->insert(array_map(
            static fn (int $id): array => ['id' => $id, 'quest_id' => $id, 'visible_in_quest_list' => true],
            $ids,
        ));
    }

    private function action(int|string $step): string
    {
        return json_encode(['setQuestStep' => ['value' => $step]]);
    }

    private function node(int $id, int $dialogId, string $type, ?string $actions = null): void
    {
        DB::table('dialog_nodes')->insert(['id' => $id, 'source_dialog_id' => $dialogId, 'type' => $type, 'additional_actions' => $actions]);
    }

    private function edge(int $dialogId, int $target, ?int $sourceNode = null, ?int $sourceOption = null, array $rules = []): void
    {
        DB::table('dialog_edges')->insert([
            'source_dialog_id' => $dialogId,
            'target_node_id' => $target,
            'source_node_id' => $sourceNode,
            'source_option_id' => $sourceOption,
            'rules' => json_encode($rules),
        ]);
    }

    private function requirements(int $dialogId): array
    {
        return json_decode(DB::table(DialogQuestAvailabilityService::TABLE)->where('dialog_id', $dialogId)->value('requirements'), true);
    }
}

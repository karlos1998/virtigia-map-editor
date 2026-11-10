<?php

namespace Tests\Unit;

use App\Services\QuestWalkthrough\QuestWalkthroughGenerator;
use App\Services\QuestWalkthrough\QuestWalkthroughWorldData;
use PHPUnit\Framework\TestCase;

class QuestWalkthroughGeneratorTest extends TestCase
{
    private const QUEST = 1;

    private array $nodes = [];

    private array $options = [];

    private array $edgesByOption = [];

    private array $edgesByNode = [];

    private int $nextId = 1;

    public function test_it_replays_a_quest_with_dialogs_waiting_costs_and_rewards(): void
    {
        $walkthrough = $this->generator($this->tavernQuest())->generate(self::QUEST);

        $this->assertSame([], $walkthrough['warnings']);
        $this->assertSame('complete', $walkthrough['status']);
        $this->assertTrue($walkthrough['finished']);
        $this->assertSame(['dialog', 'wait', 'dialog'], array_column($walkthrough['sections'], 'type'));

        [$start, $wait, $finish] = $walkthrough['sections'];

        $this->assertSame(['Słucham?', 'Pomogę.', 'Zakończ rozmowę.'], $this->labels($start));
        $this->assertSame('Karczmarz', $start['npcs'][0]['name']);
        $this->assertSame([['map_id' => 7, 'map_name' => 'Karczma', 'x' => 6, 'y' => 18]], $start['npcs'][0]['locations']);
        $this->assertSame(1200, $wait['seconds']);

        // The quest item from the first talk satisfies the item branch, so the player is not sent back.
        $this->assertSame(['Mam księgę.', 'Zakończ rozmowę.'], $this->labels($finish));
        $this->assertSame(30, end($finish['reached_steps'])['id']);

        $summary = $walkthrough['summary'];
        $this->assertSame(50, $summary['required_level']);
        $this->assertSame(1000, $summary['gold_cost']);
        $this->assertSame(1200, $summary['wait_seconds']);
        $this->assertSame(500, $summary['rewards']['exp']);
        // The book is handed back, only the coin remains as a reward.
        $this->assertSame([['item_id' => 901, 'quantity' => 3]], $summary['rewards']['items']);
    }

    public function test_option_rules_are_checked_before_the_node_actions_run(): void
    {
        $node = $this->special(10, 'Witaj', ['setQuestStep' => ['value' => 10]]);
        $start = $this->start(10, $node);
        $this->option($node, 'Widoczne tylko przed questem', ['questBeforeStep' => ['value' => 'q-'.self::QUEST]]);
        $later = $this->option($node, 'Dalej');
        $this->edge($later, $this->special(10, 'Koniec', ['setQuestStep' => ['value' => 20]]));

        $walkthrough = $this->generator([10 => 'Strażnik'], [
            ['id' => 10, 'name' => 'Pierwszy'],
            ['id' => 20, 'name' => 'Drugi'],
        ])->generate(self::QUEST);

        $this->assertSame('complete', $walkthrough['status']);
        // The quest starts on dialog open; the next step is one click away in the same conversation.
        $this->assertSame([10, 20], array_column($walkthrough['sections'][0]['reached_steps'], 'id'));
        $this->assertSame(['Dalej'], $this->labels($walkthrough['sections'][0]));
        $this->assertNotNull($start);
    }

    public function test_it_reports_a_step_nothing_can_move_past(): void
    {
        $node = $this->special(10, 'Hej', []);
        $this->start(10, $node);
        $option = $this->option($node, 'Zaczynam');
        $this->edge($option, $this->special(10, 'Dobrze', ['setQuestStep' => ['value' => 10]]));
        // Step 20 is set by a dialog that opens only on step 30, which nothing ever sets.
        $other = $this->special(11, 'Ukryte', ['setQuestStep' => ['value' => 20]]);
        $this->start(11, $other, ['questStep' => ['value' => 's-30']]);

        $walkthrough = $this->generator([10 => 'Strażnik', 11 => 'Mnich'], [
            ['id' => 10, 'name' => 'Pierwszy'],
            ['id' => 20, 'name' => 'Drugi'],
            ['id' => 30, 'name' => 'Trzeci'],
        ])->generate(self::QUEST);

        $this->assertSame('partial', $walkthrough['status']);
        $this->assertStringContainsString('„Pierwszy”', $walkthrough['warnings'][0]);
    }

    private function tavernQuest(): array
    {
        // Talk 1: start quest, receive a book (quest item) and a coin.
        $hello = $this->special(100, 'Szukasz pracy?', []);
        $this->start(100, $hello, ['questBeforeStep' => ['value' => 'q-'.self::QUEST]]);
        $listen = $this->option($hello, 'Słucham?');
        $offer = $this->special(100, 'Zanieś księgę.', []);
        $this->edge($listen, $offer);
        $this->option($offer, 'Nie interesuje mnie to.');
        $accept = $this->option($offer, 'Pomogę.', ['level' => ['value' => 50]]);
        $accepted = $this->special(100, 'Dziękuję.', [
            'setQuestStep' => ['value' => 10],
            'addItems' => ['value' => [900, 901], 'value2' => [1, 3]],
        ]);
        $this->edge($accept, $accepted);
        $this->option($accepted, 'Zakończ rozmowę.');

        // Talk 2 (after the timer): paying gold, with an item branch that is evaluated first.
        $back = $this->special(100, 'Wróciłeś!', []);
        $this->start(100, $back, ['questStep' => ['value' => 's-20']]);
        $book = $this->option($back, 'Mam księgę.', ['gold' => ['value' => 1000, 'consume' => true]]);
        $failure = $this->special(100, 'Nie masz księgi, wracaj.', ['setQuestStep' => ['value' => 10]]);
        $success = $this->special(100, 'Świetnie.', ['setQuestStep' => ['value' => 30], 'addExp' => ['value' => 500]]);
        $this->edge($book, $success, ['items' => ['value' => [900], 'value2' => [1], 'consume' => true]]);
        $this->edge($book, $failure);
        $this->option($success, 'Zakończ rozmowę.');
        $this->option($failure, 'Zakończ rozmowę.');

        return [100 => 'Karczmarz'];
    }

    private function generator(array $dialogNpcs, ?array $steps = null): QuestWalkthroughGenerator
    {
        $steps ??= [
            ['id' => 10, 'name' => 'Poczekaj', 'auto_progress' => ['type' => 'time', 'time_seconds' => 1200, 'mobs' => []]],
            ['id' => 20, 'name' => 'Wróć do karczmarza'],
            ['id' => 30, 'name' => 'Ukończone', 'visible_in_quest_list' => false],
        ];

        $npcsByDialog = [];
        $npcId = 1;
        foreach ($dialogNpcs as $dialogId => $name) {
            $npcsByDialog[$dialogId][] = [
                'id' => $npcId,
                'dialog_id' => $dialogId,
                'base_npc_id' => $npcId,
                'auto_start_dialog' => false,
                // Every NPC also stands on an internal admin map, which must never be suggested.
                'locations' => [['map_id' => 99, 'x' => 31, 'y' => 11], ['map_id' => 7, 'x' => 6, 'y' => 18]],
            ];
            $baseNpcs[$npcId] = ['id' => $npcId, 'name' => $name, 'lvl' => 10, 'src' => null, 'rank' => null];
            $npcId++;
        }

        return new QuestWalkthroughGenerator(new QuestWalkthroughWorldData(
            quests: [self::QUEST => ['id' => self::QUEST, 'name' => 'Księga dla karczmarza']],
            stepsByQuest: [self::QUEST => array_map(fn (array $step) => [
                'quest_id' => self::QUEST,
                'description' => null,
                'visible_in_quest_list' => true,
                'auto_advance_next_day' => false,
                'auto_advance_to_step_id' => null,
                'auto_progress' => null,
                ...$step,
            ], $steps)],
            dialogs: array_map(fn ($name) => ['id' => 0, 'name' => $name], $dialogNpcs),
            nodes: $this->nodes,
            optionsByNode: $this->options,
            edgesByOption: $this->edgesByOption,
            edgesByNode: $this->edgesByNode,
            npcsByDialog: $npcsByDialog,
            npcsByBaseNpc: [],
            baseNpcs: $baseNpcs ?? [],
            baseNpcIdsBySpecies: [],
            mobSpecies: [],
            maps: [7 => 'Karczma', 99 => 'Siedziba MG'],
            items: [900 => ['id' => 900, 'name' => 'Księga', 'src' => null], 901 => ['id' => 901, 'name' => 'Denar', 'src' => null]],
            internalMapIds: [99 => true],
        ));
    }

    private function labels(array $section): array
    {
        return array_values(array_map(fn (array $entry) => $entry['label'], array_filter($section['timeline'], fn (array $entry) => $entry['type'] === 'choose')));
    }

    private function start(int $dialogId, int $target, array $rules = []): int
    {
        $startId = null;
        foreach ($this->nodes as $node) {
            if ($node['dialog_id'] === $dialogId && $node['type'] === 'start') {
                $startId = $node['id'];
            }
        }
        $startId ??= $this->node($dialogId, 'start', null, []);

        $this->edgesByNode[$startId][] = ['id' => $this->nextId++, 'source_node_id' => $startId, 'source_option_id' => null, 'source_handle' => null, 'target_node_id' => $target, 'rules' => $rules];

        return $startId;
    }

    private function special(int $dialogId, string $content, array $actions): int
    {
        return $this->node($dialogId, 'special', $content, $actions);
    }

    private function node(int $dialogId, string $type, ?string $content, array $actions): int
    {
        $id = $this->nextId++;
        $this->nodes[$id] = [
            'id' => $id,
            'dialog_id' => $dialogId,
            'type' => $type,
            'content' => $content,
            'action_data' => null,
            'additional_actions' => $actions ?: null,
            'shop_id' => null,
            'hotel_id' => null,
        ];

        return $id;
    }

    private function option(int $nodeId, string $label, array $rules = []): int
    {
        $id = $this->nextId++;
        $this->options[$nodeId][] = [
            'id' => $id,
            'node_id' => $nodeId,
            'label' => $label,
            'order' => count($this->options[$nodeId] ?? []),
            'rules' => $rules,
            'additional_action' => null,
            'additional_actions' => null,
        ];

        return $id;
    }

    private function edge(int $optionId, int $target, array $rules = []): void
    {
        $this->edgesByOption[$optionId][] = ['id' => $this->nextId++, 'source_node_id' => null, 'source_option_id' => $optionId, 'source_handle' => null, 'target_node_id' => $target, 'rules' => $rules];
    }
}

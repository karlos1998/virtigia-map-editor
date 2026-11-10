<?php

namespace App\Services\QuestWalkthrough;

/**
 * Read-only snapshot of every world-template record the walkthrough generator needs.
 *
 * Kept as plain arrays so the generator can be exercised without a database.
 */
class QuestWalkthroughWorldData
{
    /** @var array<int, array<int, int>> quest id => dialog ids that change one of its steps */
    private array $questDialogIds = [];

    /** @var array<int, array<int, int>> base npc id => dialog ids offering a battle */
    private array $battleDialogIdsByBaseNpc = [];

    /** @var array<int, int> dialog id => start node id */
    private array $startNodeByDialog = [];

    /** @var array<int, int> step id => quest id */
    private array $stepQuest = [];

    /** @var array<int, array<int, int>> item id => dialog ids handing it out */
    private array $itemGiverDialogIds = [];

    /** @var array<int, true> step ids set by any dialog */
    private array $stepsSetByDialogs = [];

    /** @var array<int, array<int, int>> dialog id => node ids */
    private array $nodeIdsByDialog = [];

    /**
     * @param  array<int, array{id: int, name: string}>  $quests
     * @param  array<int, array<int, array<string, mixed>>>  $stepsByQuest  steps sorted by id
     * @param  array<int, array{id: int, name: ?string}>  $dialogs
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<int, array<int, array<string, mixed>>>  $optionsByNode  sorted by order
     * @param  array<int, array<int, array<string, mixed>>>  $edgesByOption  sorted by id
     * @param  array<int, array<int, array<string, mixed>>>  $edgesByNode  direct (option-less) edges sorted by id
     * @param  array<int, array<int, array<string, mixed>>>  $npcsByDialog
     * @param  array<int, array<int, array<string, mixed>>>  $npcsByBaseNpc
     * @param  array<int, array<string, mixed>>  $baseNpcs
     * @param  array<int, array<int, int>>  $baseNpcIdsBySpecies
     * @param  array<int, string>  $mobSpecies
     * @param  array<int, string>  $maps
     * @param  array<int, array{id: int, name: string, src: ?string}>  $items
     * @param  array<int, array<int, array<string, mixed>>>  $itemSources
     * @param  array<int, string>  $dialogCounters
     * @param  array<int, string>  $seasonalEvents
     */
    public function __construct(
        public readonly array $quests,
        public readonly array $stepsByQuest,
        public readonly array $dialogs,
        public readonly array $nodes,
        public readonly array $optionsByNode,
        public readonly array $edgesByOption,
        public readonly array $edgesByNode,
        public readonly array $npcsByDialog,
        public readonly array $npcsByBaseNpc,
        public readonly array $baseNpcs,
        public readonly array $baseNpcIdsBySpecies,
        public readonly array $mobSpecies,
        public readonly array $maps,
        public readonly array $items,
        public readonly array $itemSources = [],
        public readonly array $dialogCounters = [],
        public readonly array $seasonalEvents = [],
    ) {
        $this->indexDialogs();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function steps(int $questId): array
    {
        return $this->stepsByQuest[$questId] ?? [];
    }

    /**
     * @return array<int, int>
     */
    public function dialogIdsChangingQuest(int $questId): array
    {
        return $this->questDialogIds[$questId] ?? [];
    }

    /**
     * @return array<int, int>
     */
    public function battleDialogIdsForBaseNpc(int $baseNpcId): array
    {
        return $this->battleDialogIdsByBaseNpc[$baseNpcId] ?? [];
    }

    public function startNodeId(int $dialogId): ?int
    {
        return $this->startNodeByDialog[$dialogId] ?? null;
    }

    /**
     * @return array<int, int>
     */
    public function nodeIdsOfDialog(int $dialogId): array
    {
        return $this->nodeIdsByDialog[$dialogId] ?? [];
    }

    /**
     * @return array<int, int>
     */
    public function dialogIdsGivingItem(int $itemId): array
    {
        return $this->itemGiverDialogIds[$itemId] ?? [];
    }

    public function isStepSetByDialog(int $stepId): bool
    {
        return isset($this->stepsSetByDialogs[$stepId]);
    }

    public function questIdForStep(int $stepId): ?int
    {
        return $this->stepQuest[$stepId] ?? null;
    }

    public function itemName(int $itemId): string
    {
        return $this->items[$itemId]['name'] ?? "Przedmiot #{$itemId}";
    }

    public function mapName(int $mapId): string
    {
        return $this->maps[$mapId] ?? "Mapa #{$mapId}";
    }

    public function baseNpcName(int $baseNpcId): string
    {
        return $this->baseNpcs[$baseNpcId]['name'] ?? "NPC #{$baseNpcId}";
    }

    private function indexDialogs(): void
    {
        $stepQuest = [];
        foreach ($this->stepsByQuest as $questId => $steps) {
            foreach ($steps as $step) {
                $stepQuest[$step['id']] = $questId;
            }
        }
        $this->stepQuest = $stepQuest;

        $register = function (int $dialogId, mixed $actions) use ($stepQuest): void {
            $givenItems = data_get($actions, 'addItems.value');
            foreach (is_array($givenItems) ? $givenItems : [$givenItems] as $itemId) {
                if (is_numeric($itemId)) {
                    $this->itemGiverDialogIds[(int) $itemId][$dialogId] = $dialogId;
                }
            }

            $stepId = data_get($actions, 'setQuestStep.value');
            if (is_numeric($stepId)) {
                $this->stepsSetByDialogs[(int) $stepId] = true;
            }
            if (is_numeric($stepId) && isset($stepQuest[(int) $stepId])) {
                $this->questDialogIds[$stepQuest[(int) $stepId]][$dialogId] = $dialogId;
            }
        };

        foreach ($this->nodes as $node) {
            $this->nodeIdsByDialog[$node['dialog_id']][] = $node['id'];

            if ($node['type'] === 'start' && ! isset($this->startNodeByDialog[$node['dialog_id']])) {
                $this->startNodeByDialog[$node['dialog_id']] = $node['id'];
            }

            $register($node['dialog_id'], $node['additional_actions']);

            foreach ($this->optionsByNode[$node['id']] ?? [] as $option) {
                $register($node['dialog_id'], $option['additional_actions']);

                if (in_array($option['additional_action'], ['BATTLE', 'KILL_AND_LOOT'], true)) {
                    foreach ($this->npcsByDialog[$node['dialog_id']] ?? [] as $npc) {
                        if ($npc['base_npc_id'] !== null) {
                            $this->battleDialogIdsByBaseNpc[$npc['base_npc_id']][$node['dialog_id']] = $node['dialog_id'];
                        }
                    }
                }
            }
        }

        foreach ($this->questDialogIds as $questId => $dialogIds) {
            ksort($dialogIds);
            $this->questDialogIds[$questId] = array_values($dialogIds);
        }

        foreach ($this->itemGiverDialogIds as $itemId => $dialogIds) {
            ksort($dialogIds);
            $this->itemGiverDialogIds[$itemId] = array_values($dialogIds);
        }

        foreach ($this->battleDialogIdsByBaseNpc as $baseNpcId => $dialogIds) {
            $this->battleDialogIdsByBaseNpc[$baseNpcId] = array_values($dialogIds);
        }
    }
}

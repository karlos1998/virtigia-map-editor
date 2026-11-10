<?php

namespace App\Services\QuestWalkthrough;

/**
 * Builds a player-facing walkthrough of a quest from world-template data.
 *
 * The quest is replayed from the "not started" state: every section is either a conversation
 * found by {@see QuestDialogSimulator}, or an automatic progression (timer, mob kills, next day).
 */
class QuestWalkthroughGenerator
{
    /** Bump whenever the output shape or interpretation changes; forces regeneration. */
    public const VERSION = 3;

    private const MAX_LOCATIONS = 12;

    /** How many alternative conversations may be tried in total when backtracking. */
    private const SEARCH_BUDGET = 40;

    /** Seconds after which no further alternatives are tried for one quest. */
    private const TIME_LIMIT_SECONDS = 4.0;

    private float $deadline = 0.0;

    private const MAX_ITEM_SOURCES = 6;

    public function __construct(private readonly QuestWalkthroughWorldData $data) {}

    public function generate(int $questId): array
    {
        $quest = $this->data->quests[$questId] ?? ['id' => $questId, 'name' => "Quest #{$questId}"];
        $steps = $this->data->steps($questId);
        $simulator = new QuestDialogSimulator($this->data, $questId);

        if ($steps === []) {
            $result = ['sections' => [], 'warnings' => ['Quest nie ma żadnych kroków.'], 'finished' => false];
        } else {
            $budget = self::SEARCH_BUDGET;
            $this->deadline = microtime(true) + self::TIME_LIMIT_SECONDS;
            $result = $this->walk($simulator, $steps, QuestDialogSimulator::initialState(), [], [], $budget);
        }

        $sections = $this->decorateSections($result['sections'], collect($steps)->keyBy('id')->all());

        return [
            'quest' => ['id' => $quest['id'], 'name' => $quest['name']],
            'status' => $sections === [] ? 'empty' : ($result['warnings'] === [] ? 'complete' : 'partial'),
            'finished' => $result['finished'],
            'summary' => $this->summary($sections),
            'sections' => $sections,
            'warnings' => $result['warnings'],
            'references' => $this->references($sections),
            'generator_version' => self::VERSION,
        ];
    }

    /**
     * Depth-first replay of the quest. Conversations are tried best-first; when a branch leads to a
     * dead end (e.g. the shortest talk handed out the wrong item), the next alternative is tried.
     *
     * @return array{sections: array, warnings: array, finished: bool}
     */
    private function walk(QuestDialogSimulator $simulator, array $steps, array $state, array $visitedSteps, array $sections, int &$budget): array
    {
        $stepsById = collect($steps)->keyBy('id')->all();

        while (count($sections) <= count($steps) * 3 + 5) {
            $current = $state['step'] !== null ? ($stepsById[$state['step']] ?? null) : null;

            if ($current !== null && ($automatic = $this->automaticSection($current, $steps, $simulator->questId(), $state)) !== null) {
                $sections[] = $automatic['section'];
                $nextStepId = $automatic['next_step_id'];

                if ($nextStepId === null || isset($visitedSteps[$nextStepId])) {
                    return ['sections' => $sections, 'warnings' => [], 'finished' => true];
                }

                $visitedSteps[$nextStepId] = true;
                $state['step'] = $nextStepId;

                continue;
            }

            $candidates = $this->findConversations($simulator, $state, $visitedSteps, false);
            if ($candidates === []) {
                $candidates = $this->findConversations($simulator, $state, $visitedSteps, true);
            }
            $sideTrip = false;
            if ($candidates === []) {
                $deadEnd = $this->deadEnd($current, $sections, $steps, $visitedSteps);

                // A finished quest (or one that never started) needs no detour for missing items.
                if ($deadEnd['warnings'] === [] || ! $state['quest_started']) {
                    return $deadEnd;
                }

                $candidates = $this->findItemConversations($simulator, $state);
                $sideTrip = true;
            }

            if ($candidates === []) {
                return $this->deadEnd($current, $sections, $steps, $visitedSteps);
            }

            $best = null;
            foreach ($candidates as $index => $candidate) {
                if ($index > 0 && (--$budget <= 0 || microtime(true) > $this->deadline)) {
                    break;
                }

                $nextVisited = $visitedSteps;
                foreach ($candidate['visited_steps'] as $stepId) {
                    $nextVisited[$stepId] = true;
                }
                $nextState = $candidate['state'];
                unset($candidate['state'], $candidate['visited_steps']);

                $outcome = $this->walk($simulator, $steps, $nextState, $nextVisited, [...$sections, $candidate], $budget);

                // An item detour only counts when the quest actually moves on afterwards.
                if ($sideTrip && $this->progress($outcome['sections']) <= $this->progress($sections)) {
                    continue;
                }

                if ($outcome['warnings'] === []) {
                    return $outcome;
                }

                if ($best === null || $this->progress($outcome['sections']) > $this->progress($best['sections'])) {
                    $best = $outcome;
                }
            }

            return $best ?? $this->deadEnd($current, $sections, $steps, $visitedSteps);
        }

        return ['sections' => $sections, 'warnings' => ['Przerwano analizę: quest wydaje się zapętlony.'], 'finished' => false];
    }

    private function progress(array $sections): int
    {
        return count(array_filter($sections, fn (array $section): bool => ($section['purpose'] ?? null) !== 'item'));
    }

    /**
     * @return array{sections: array, warnings: array, finished: bool}
     */
    private function deadEnd(?array $current, array $sections, array $steps, array $visitedSteps): array
    {
        if ($current === null) {
            return ['sections' => $sections, 'warnings' => ['Nie znaleziono dialogu, który rozpoczyna ten quest.'], 'finished' => false];
        }

        // Nothing later in the quest can ever be activated: the current step is effectively the last one.
        $reachableLater = collect($steps)->contains(fn (array $step): bool => $step['id'] > $current['id']
            && ! isset($visitedSteps[$step['id']])
            && $this->data->isStepSetByDialog($step['id']));

        if (! $reachableLater) {
            return ['sections' => $sections, 'warnings' => [], 'finished' => true];
        }

        return [
            'sections' => $sections,
            'warnings' => ["Nie znaleziono sposobu na przejście dalej z kroku „{$current['name']}” (ID {$current['id']})."],
            'finished' => false,
        ];
    }

    /**
     * @return array{section: array, next_step_id: ?int}|null
     */
    private function automaticSection(array $step, array $steps, int $questId, array $state): ?array
    {
        $progress = $step['auto_progress'];

        if ($progress !== null && $progress['type'] === 'time') {
            return [
                'section' => [
                    'type' => 'wait',
                    'step_id' => $step['id'],
                    'seconds' => (int) $progress['time_seconds'],
                ],
                'next_step_id' => $this->nextStepId($step['id'], $steps),
            ];
        }

        if ($progress !== null && $progress['type'] === 'mobs') {
            return [
                'section' => [
                    'type' => 'kill',
                    'step_id' => $step['id'],
                    'targets' => array_map(fn (array $mob) => $this->killTarget($mob, $questId, $state), $progress['mobs']),
                ],
                'next_step_id' => $this->nextStepId($step['id'], $steps),
            ];
        }

        if ($step['auto_advance_next_day']) {
            return [
                'section' => [
                    'type' => 'next_day',
                    'step_id' => $step['id'],
                    'resets_quest' => $step['auto_advance_to_step_id'] === null,
                ],
                'next_step_id' => $step['auto_advance_to_step_id'],
            ];
        }

        return null;
    }

    /**
     * Conversations that move the quest forward from the current state, best first.
     *
     * @return array<int, array>
     */
    private function findConversations(QuestDialogSimulator $simulator, array $state, array $visitedSteps, bool $assumeOtherQuests): array
    {
        $simulator->setAssumeOtherQuests($assumeOtherQuests);
        $initialStep = $state['step'];
        $goal = fn (array $position): bool => $this->advancesQuest($position, $initialStep, $visitedSteps);

        $candidates = [];

        foreach ($this->data->dialogIdsChangingQuest($simulator->questId()) as $dialogId) {
            $npcs = $this->npcsForDialog($dialogId);

            foreach ($simulator->searchMany($dialogId, $state, $goal) as $found) {
                foreach ($this->continueConversation($simulator, $dialogId, $found, $visitedSteps) as $position) {
                    $signature = json_encode($position['state']);
                    $score = [
                        $this->targetStepRank($found['state']['step'], $initialStep, $simulator->questId()),
                        $npcs === [] ? 1 : 0,
                        $found['clicks'],
                        $this->assumptionCount($position['timeline']),
                        $position['clicks'],
                    ];

                    if (isset($candidates[$signature]) && $candidates[$signature]['score'] <= $score) {
                        continue;
                    }

                    $visited = [];
                    foreach ($position['timeline'] as $entry) {
                        if ($entry['type'] === 'step') {
                            $visited[] = $entry['to'];
                        }
                    }

                    $candidates[$signature] = [
                        'score' => $score,
                        'section' => [
                            'type' => 'dialog',
                            'dialog_id' => $dialogId,
                            'dialog_name' => $this->data->dialogs[$dialogId]['name'] ?? null,
                            'npcs' => $npcs,
                            'timeline' => $this->compactTimeline($position['timeline']),
                            'ends_with' => $position['kind'] === 'end' ? $position['reason'] : 'open',
                            'assumes_other_quests' => $assumeOtherQuests,
                            'state' => $position['state'],
                            'visited_steps' => $visited,
                        ],
                    ];
                }
            }
        }

        uasort($candidates, fn (array $a, array $b): int => $a['score'] <=> $b['score']);

        return array_values(array_map(fn (array $candidate) => $candidate['section'], $candidates));
    }

    /**
     * Side conversations with NPCs that hand out an item the quest dialogs ask for but the player lacks.
     *
     * @return array<int, array>
     */
    private function findItemConversations(QuestDialogSimulator $simulator, array $state): array
    {
        $simulator->setAssumeOtherQuests(true);
        $questDialogIds = $this->data->dialogIdsChangingQuest($simulator->questId());
        $candidates = [];

        foreach ($this->requiredItemIds($questDialogIds) as $itemId) {
            if (($state['items'][$itemId] ?? 0) > 0) {
                continue;
            }

            foreach (array_slice($this->data->dialogIdsGivingItem($itemId), 0, 5) as $dialogId) {
                if (in_array($dialogId, $questDialogIds, true)) {
                    continue;
                }

                $found = $simulator->search($dialogId, $state, fn (array $p): bool => ($p['state']['items'][$itemId] ?? 0) > 0 && $p['state']['step'] === $state['step']);
                if ($found === null) {
                    continue;
                }

                $position = $this->continueConversation($simulator, $dialogId, $found, [])[0];
                $candidates[] = [
                    'score' => [$found['clicks'], $this->assumptionCount($position['timeline'])],
                    'section' => [
                        'type' => 'dialog',
                        'purpose' => 'item',
                        'item_id' => $itemId,
                        'dialog_id' => $dialogId,
                        'dialog_name' => $this->data->dialogs[$dialogId]['name'] ?? null,
                        'npcs' => $this->npcsForDialog($dialogId),
                        'timeline' => $this->compactTimeline($position['timeline']),
                        'ends_with' => $position['kind'] === 'end' ? $position['reason'] : 'open',
                        'assumes_other_quests' => true,
                        'state' => $position['state'],
                        'visited_steps' => [],
                    ],
                ];
            }
        }

        usort($candidates, fn (array $a, array $b): int => $a['score'] <=> $b['score']);

        return array_slice(array_map(fn (array $candidate) => $candidate['section'], $candidates), 0, 3);
    }

    /**
     * @param  array<int, int>  $dialogIds
     * @return array<int, int>
     */
    private function requiredItemIds(array $dialogIds): array
    {
        $itemIds = [];
        $collect = function (mixed $rules) use (&$itemIds): void {
            foreach (['items', 'equippedItems'] as $key) {
                $value = data_get($rules, "{$key}.value");
                foreach (is_array($value) ? $value : [$value] as $itemId) {
                    if (is_numeric($itemId)) {
                        $itemIds[(int) $itemId] = (int) $itemId;
                    }
                }
            }
        };

        foreach ($dialogIds as $dialogId) {
            foreach ($this->data->nodeIdsOfDialog($dialogId) as $nodeId) {
                foreach ($this->data->edgesByNode[$nodeId] ?? [] as $edge) {
                    $collect($edge['rules']);
                }
                foreach ($this->data->optionsByNode[$nodeId] ?? [] as $option) {
                    $collect($option['rules']);
                    foreach ($this->data->edgesByOption[$option['id']] ?? [] as $edge) {
                        $collect($edge['rules']);
                    }
                }
            }
        }

        return array_values($itemIds);
    }

    /**
     * After the decisive click, keeps following the conversation: first to further quest steps reachable
     * in the same talk, then along the shortest ways that close the dialog (one per resulting player state).
     *
     * @return array<int, array>
     */
    private function continueConversation(QuestDialogSimulator $simulator, int $dialogId, array $position, array $visitedSteps): array
    {
        for ($i = 0; $i < 10 && $position['kind'] === 'node'; $i++) {
            foreach ($position['timeline'] as $entry) {
                if ($entry['type'] === 'step') {
                    $visitedSteps[$entry['to']] = true;
                }
            }

            $step = $position['state']['step'];
            $next = $simulator->search($dialogId, $position['state'], fn (array $p): bool => $this->advancesQuest($p, $step, $visitedSteps), $position);
            if ($next === null) {
                break;
            }
            $position = $next;
        }

        if ($position['kind'] !== 'node') {
            return [$position];
        }

        $step = $position['state']['step'];
        $endings = $simulator->searchMany($dialogId, $position['state'], fn (array $p): bool => $p['kind'] === 'end' && $p['state']['step'] === $step, $position);

        usort($endings, fn (array $a, array $b): int => [$a['reason'] !== 'close', $a['clicks']] <=> [$b['reason'] !== 'close', $b['clicks']]);

        return $endings !== [] ? $endings : [$position];
    }

    private function advancesQuest(array $position, ?int $initialStep, array $visitedSteps): bool
    {
        $step = $position['state']['step'];

        return $step !== null && $step !== $initialStep && ! isset($visitedSteps[$step]);
    }

    /**
     * Prefers the nearest following step, then any other unvisited step. Jumping straight to a hidden
     * step that is not the next one (typically a "quest finished" marker) is the last resort.
     */
    private function targetStepRank(?int $targetStep, ?int $initialStep, int $questId): int
    {
        if ($targetStep === null) {
            return PHP_INT_MAX;
        }

        $steps = $this->data->steps($questId);
        $positions = array_flip(array_column($steps, 'id'));
        $target = $positions[$targetStep] ?? count($steps);
        $current = $initialStep === null ? -1 : ($positions[$initialStep] ?? -1);
        $distance = $target > $current ? $target - $current : 10_000 + $target;

        if ($distance > 1 && ! ($steps[$target]['visible_in_quest_list'] ?? true)) {
            $distance += 1_000;
        }

        return $distance;
    }

    private function assumptionCount(array $timeline): int
    {
        $count = 0;
        foreach ($timeline as $entry) {
            $count += count($entry['requirements'] ?? []);
        }

        return $count;
    }

    /**
     * Folds NPC lines into the click that follows them and drops empty conditions.
     */
    private function compactTimeline(array $timeline): array
    {
        $compact = [];
        $pendingNpcText = null;

        foreach ($timeline as $entry) {
            if ($entry['type'] === 'npc') {
                $pendingNpcText = $entry['text'];

                continue;
            }

            if ($entry['type'] === 'choose') {
                $entry['npc_text'] = $pendingNpcText;
                $pendingNpcText = null;
            }

            $compact[] = $entry;
        }

        return $compact;
    }

    private function killTarget(array $mob, int $questId, array $state): array
    {
        $baseNpcIds = $mob['base_npc_id'] !== null
            ? [$mob['base_npc_id']]
            : ($this->data->baseNpcIdsBySpecies[$mob['mob_species_id']] ?? []);

        $npcs = [];
        foreach (array_slice($baseNpcIds, 0, 15) as $baseNpcId) {
            $locations = [];
            foreach ($this->data->npcsByBaseNpc[$baseNpcId] ?? [] as $npc) {
                foreach ($this->playerLocations($npc['locations']) as $location) {
                    $locations[] = $this->location($location);
                }
            }

            $battles = [];
            foreach (array_slice($this->data->battleDialogIdsForBaseNpc($baseNpcId), 0, 2) as $dialogId) {
                $battle = $this->battlePath($dialogId, $questId, $state);
                if ($battle !== null) {
                    $battles[] = $battle;
                }
            }

            $npcs[] = [
                'base_npc_id' => $baseNpcId,
                'name' => $this->data->baseNpcName($baseNpcId),
                'lvl' => $this->data->baseNpcs[$baseNpcId]['lvl'] ?? null,
                'src' => $this->data->baseNpcs[$baseNpcId]['src'] ?? null,
                'locations' => array_slice($this->uniqueLocations($locations), 0, self::MAX_LOCATIONS),
                'locations_total' => count($this->uniqueLocations($locations)),
                'battle_dialogs' => $battles,
            ];
        }

        return [
            'quantity' => $mob['quantity'],
            'base_npc_id' => $mob['base_npc_id'],
            'mob_species_id' => $mob['mob_species_id'],
            'name' => $mob['base_npc_id'] !== null
                ? $this->data->baseNpcName($mob['base_npc_id'])
                : ($this->data->mobSpecies[$mob['mob_species_id']] ?? "Gatunek #{$mob['mob_species_id']}"),
            'npcs' => $npcs,
        ];
    }

    /**
     * Clicks needed to start a fight with an NPC whose dialog offers one.
     */
    private function battlePath(int $dialogId, int $questId, array $state): ?array
    {
        $simulator = new QuestDialogSimulator($this->data, $questId);
        $simulator->setAssumeOtherQuests(true);
        $found = $simulator->search($dialogId, $state, fn (array $p): bool => $p['kind'] === 'end' && in_array($p['reason'], ['battle', 'kill_and_loot'], true));

        if ($found === null) {
            return null;
        }

        return [
            'dialog_id' => $dialogId,
            'auto_start' => collect($this->data->npcsByDialog[$dialogId] ?? [])->contains(fn (array $npc) => $npc['auto_start_dialog']),
            'timeline' => $this->compactTimeline($found['timeline']),
        ];
    }

    private function npcsForDialog(int $dialogId): array
    {
        $grouped = [];

        foreach ($this->data->npcsByDialog[$dialogId] ?? [] as $npc) {
            $locations = $this->playerLocations($npc['locations']);
            // An NPC placed only on internal maps cannot be visited by players.
            if ($locations === [] && $npc['locations'] !== []) {
                continue;
            }

            $key = $npc['base_npc_id'] ?? 'npc-'.$npc['id'];
            $grouped[$key] ??= [
                'base_npc_id' => $npc['base_npc_id'],
                'name' => $npc['base_npc_id'] !== null ? $this->data->baseNpcName($npc['base_npc_id']) : "NPC #{$npc['id']}",
                'src' => $npc['base_npc_id'] !== null ? ($this->data->baseNpcs[$npc['base_npc_id']]['src'] ?? null) : null,
                'auto_start_dialog' => false,
                'locations' => [],
            ];
            $grouped[$key]['auto_start_dialog'] = $grouped[$key]['auto_start_dialog'] || $npc['auto_start_dialog'];

            foreach ($locations as $location) {
                $grouped[$key]['locations'][] = $this->location($location);
            }
        }

        return array_values(array_map(function (array $npc): array {
            $unique = $this->uniqueLocations($npc['locations']);
            $npc['locations'] = array_slice($unique, 0, self::MAX_LOCATIONS);
            $npc['locations_total'] = count($unique);

            return $npc;
        }, $grouped));
    }

    /**
     * @return array<int, array>
     */
    private function playerLocations(array $locations): array
    {
        return array_values(array_filter($locations, fn (array $location): bool => ! $this->data->isInternalMap($location['map_id'])));
    }

    private function location(array $location): array
    {
        return [
            'map_id' => $location['map_id'],
            'map_name' => $this->data->mapName($location['map_id']),
            'x' => $location['x'],
            'y' => $location['y'],
        ];
    }

    private function uniqueLocations(array $locations): array
    {
        $unique = [];
        foreach ($locations as $location) {
            $unique["{$location['map_id']}:{$location['x']}:{$location['y']}"] = $location;
        }

        return array_values($unique);
    }

    private function nextStepId(int $stepId, array $steps): ?int
    {
        foreach ($steps as $index => $step) {
            if ($step['id'] === $stepId) {
                return $steps[$index + 1]['id'] ?? null;
            }
        }

        return null;
    }

    private function isLastStep(int $stepId, array $steps): bool
    {
        return $steps !== [] && end($steps)['id'] === $stepId;
    }

    /**
     * Adds step names to every section and to step-change events.
     */
    private function decorateSections(array $sections, array $stepsById): array
    {
        $describe = fn (?int $id): ?array => $id === null || ! isset($stepsById[$id]) ? null : [
            'id' => $id,
            'name' => $stepsById[$id]['name'],
            'description' => $stepsById[$id]['description'],
            'visible_in_quest_list' => $stepsById[$id]['visible_in_quest_list'],
        ];

        foreach ($sections as $index => $section) {
            if (isset($section['step_id'])) {
                $sections[$index]['step'] = $describe($section['step_id']);
            }

            if (isset($section['timeline'])) {
                $reached = [];
                foreach ($section['timeline'] as $entryIndex => $entry) {
                    if ($entry['type'] === 'step') {
                        $sections[$index]['timeline'][$entryIndex]['step'] = $describe($entry['to']);
                        $reached[] = $describe($entry['to']);
                    }
                }
                $sections[$index]['reached_steps'] = array_values(array_filter($reached));
            }
        }

        return $sections;
    }

    private function summary(array $sections): array
    {
        $summary = [
            'required_level' => null,
            'gold_cost' => 0,
            'honor_cost' => 0,
            'dragon_tears_cost' => 0,
            'wait_seconds' => 0,
            'next_day_waits' => 0,
            'conversations' => 0,
            'fights' => 0,
            'rewards' => [
                'exp' => 0,
                'exp_percent' => 0.0,
                'gold' => 0,
                'honor_points' => 0,
                'items' => [],
                'blessings' => [],
            ],
            'required_items' => [],
        ];

        $items = [];
        $requiredItems = [];

        $collect = function (array $timeline) use (&$summary, &$items, &$requiredItems): void {
            foreach ($timeline as $entry) {
                foreach ($entry['requirements'] ?? [] as $requirement) {
                    if (($requirement['type'] ?? null) === 'level' && is_numeric($requirement['value'])) {
                        $summary['required_level'] = max((int) $summary['required_level'], (int) $requirement['value']);
                    }
                    if (in_array($requirement['type'] ?? null, ['item', 'equipped_item'], true)) {
                        $requiredItems[$requirement['item_id']] = max($requiredItems[$requirement['item_id']] ?? 0, $requirement['quantity'] ?? 1);
                    }
                }

                match ($entry['type']) {
                    'cost_gold' => $summary['gold_cost'] += $entry['value'],
                    'cost_honor_points' => $summary['honor_cost'] += $entry['value'],
                    'cost_dragon_tears' => $summary['dragon_tears_cost'] += $entry['value'],
                    'reward_exp' => $summary['rewards']['exp'] += $entry['value'],
                    'reward_exp_percent' => $summary['rewards']['exp_percent'] += $entry['value'],
                    'reward_gold' => $summary['rewards']['gold'] += $entry['value'],
                    'reward_honor_points' => $summary['rewards']['honor_points'] += $entry['value'],
                    'reward_blessing' => $summary['rewards']['blessings'][] = $entry['item_id'],
                    'option_action' => in_array($entry['action'], ['BATTLE', 'KILL_AND_LOOT'], true) ? $summary['fights']++ : null,
                    default => null,
                };

                if ($entry['type'] === 'reward_items' || $entry['type'] === 'cost_items') {
                    $sign = $entry['type'] === 'reward_items' ? 1 : -1;
                    foreach ($entry['items'] as $item) {
                        $items[$item['item_id']] = ($items[$item['item_id']] ?? 0) + $sign * $item['quantity'];
                    }
                }
            }
        };

        foreach ($sections as $section) {
            match ($section['type']) {
                'dialog' => $summary['conversations']++,
                'wait' => $summary['wait_seconds'] += $section['seconds'],
                'next_day' => $summary['next_day_waits']++,
                default => null,
            };

            if (isset($section['timeline'])) {
                $collect($section['timeline']);
            }
        }

        // Net result: quest items handed back later (books, potions...) are not a reward.
        $items = array_filter($items, fn (int $quantity): bool => $quantity > 0);
        $summary['rewards']['items'] = array_map(fn (int $id, int $quantity) => ['item_id' => $id, 'quantity' => $quantity], array_keys($items), $items);
        $summary['required_items'] = array_map(fn (int $id, int $quantity) => ['item_id' => $id, 'quantity' => $quantity], array_keys($requiredItems), $requiredItems);

        return $summary;
    }

    /**
     * Names, graphics and known sources of every item referenced by the walkthrough.
     */
    private function references(array $sections): array
    {
        $itemIds = [];
        array_walk_recursive($sections, function ($value, $key) use (&$itemIds): void {
            if ($key === 'item_id' && is_numeric($value)) {
                $itemIds[(int) $value] = true;
            }
        });

        $items = [];
        foreach (array_keys($itemIds) as $itemId) {
            $items[$itemId] = [
                'id' => $itemId,
                'name' => $this->data->itemName($itemId),
                'src' => $this->data->items[$itemId]['src'] ?? null,
                'sources' => array_slice($this->data->itemSources[$itemId] ?? [], 0, self::MAX_ITEM_SOURCES),
                'sources_total' => count($this->data->itemSources[$itemId] ?? []),
            ];
        }

        ksort($items);

        return ['items' => (object) $items];
    }
}

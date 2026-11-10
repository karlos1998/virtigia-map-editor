<?php

namespace App\Services\QuestWalkthrough;

use Closure;

/**
 * Simulates engine dialog traversal (DialogService / RuleService) for one quest.
 *
 * Quest rules of the simulated quest and items obtained during the walkthrough are evaluated
 * exactly. Everything the generator cannot know about a player (level, gold, other quests,
 * time of day, randomness...) is treated as an assumption: the edge or option may be taken,
 * and the assumption is reported as a requirement next to the click.
 */
class QuestDialogSimulator
{
    public const MAX_EXPANSIONS = 6000;

    public const MAX_CLICKS = 40;

    private const QUEST_RULES = ['questStep', 'questBeforeStep', 'questAfterStep'];

    private const CONSUMABLE_RULES = ['gold', 'honorPoints', 'dragonTears', 'items'];

    /** Option actions after which the engine closes the dialog (DialogOptionActionService returns true). */
    private const TERMINATING_OPTION_ACTIONS = ['SELF_KILL', 'BATTLE', 'KILL_AND_LOOT', 'KILL', 'SHOW_MAIL', 'SHOW_AUCTIONS', 'SHOW_DEPOSIT', 'SHOW_CLAN_DEPOSIT'];

    private const PROFESSIONS = [
        'w' => 'Wojownik',
        'p' => 'Paladyn',
        'm' => 'Mag',
        'b' => 'Tancerz ostrzy',
        't' => 'Tropiciel',
        'h' => 'Łowca',
    ];

    /** @var array<int, int> step id => position in quest */
    private array $stepPositions = [];

    /** @var array<int, true> items handed out by dialogs of this quest */
    private array $questItemIds = [];

    private bool $assumeOtherQuests = false;

    public function __construct(
        private readonly QuestWalkthroughWorldData $data,
        private readonly int $questId,
    ) {
        foreach ($data->steps($questId) as $position => $step) {
            $this->stepPositions[$step['id']] = $position;
        }

        foreach ($data->dialogIdsChangingQuest($questId) as $dialogId) {
            foreach ($data->nodeIdsOfDialog($dialogId) as $nodeId) {
                $node = $data->nodes[$nodeId];
                $this->registerQuestItems($node['additional_actions']);
                foreach ($data->optionsByNode[$node['id']] ?? [] as $option) {
                    $this->registerQuestItems($option['additional_actions']);
                }
            }
        }
    }

    /**
     * @return array{quest_started: bool, step: ?int, items: array<int, int>}
     */
    public static function initialState(): array
    {
        return ['quest_started' => false, 'step' => null, 'items' => []];
    }

    public function questId(): int
    {
        return $this->questId;
    }

    public function setAssumeOtherQuests(bool $assume): void
    {
        $this->assumeOtherQuests = $assume;
    }

    /**
     * Breadth-first search for the shortest conversation reaching a goal.
     *
     * @param  Closure(array): bool  $goal  receives a position and decides whether it ends the search
     * @return array|null position with `timeline` (clicks and events) and `state`
     */
    public function search(int $dialogId, array $state, Closure $goal, ?array $from = null): ?array
    {
        return $this->searchMany($dialogId, $state, $goal, $from, 1)[0] ?? null;
    }

    /**
     * Like {@see search()}, but returns up to $limit goals that end in different player states
     * (e.g. a step change with or without an item handed out later in the same talk).
     *
     * @return array<int, array>
     */
    public function searchMany(int $dialogId, array $state, Closure $goal, ?array $from = null, int $limit = 3): array
    {
        if ($from === null) {
            $startNodeId = $this->data->startNodeId($dialogId);
            if ($startNodeId === null) {
                return null;
            }

            $queue = [];
            foreach ($this->selectableEdges($this->data->edgesByNode[$startNodeId] ?? [], $state) as $candidate) {
                foreach ($this->traverse($candidate['edge'], $state, $candidate['assumptions'], []) as $position) {
                    $queue[] = $position;
                }
            }
        } else {
            $queue = [];
            if ($from['kind'] === 'node') {
                foreach ($from['options'] as $visibleOption) {
                    foreach ($this->click($from, $visibleOption) as $next) {
                        $queue[] = $next;
                    }
                }
            }
        }

        $visited = [];
        $expansions = 0;
        $found = [];

        while ($queue !== []) {
            $position = array_shift($queue);

            if ($goal($position)) {
                $signature = json_encode($position['state']);
                if (! isset($found[$signature])) {
                    $found[$signature] = $position;
                    if (count($found) >= $limit) {
                        break;
                    }
                }
            }

            if ($position['kind'] !== 'node' || ++$expansions > self::MAX_EXPANSIONS) {
                continue;
            }

            $key = $position['node_id'].'|'.json_encode($position['state']);
            if (isset($visited[$key]) || $position['clicks'] >= self::MAX_CLICKS) {
                continue;
            }
            $visited[$key] = true;

            foreach ($position['options'] as $visibleOption) {
                foreach ($this->click($position, $visibleOption) as $next) {
                    $queue[] = $next;
                }
            }
        }

        return array_values($found);
    }

    /**
     * Engine edge choice: edges sorted by rule count (descending) and the first satisfied one wins.
     * An edge whose rules depend on assumptions may or may not be taken, so the search may continue past it.
     *
     * @return array<int, array{edge: array, assumptions: array}>
     */
    public function selectableEdges(array $edges, array $state): array
    {
        $sorted = $edges;
        usort($sorted, fn (array $a, array $b): int => [count($b['rules'] ?? []), $a['id']] <=> [count($a['rules'] ?? []), $b['id']]);

        $candidates = [];
        foreach ($sorted as $edge) {
            if ($edge['target_node_id'] === null) {
                continue;
            }

            $evaluation = $this->evaluateRules($edge['rules'] ?? [], $state);
            if ($evaluation['status'] === 'fail') {
                continue;
            }

            $candidates[] = ['edge' => $edge, 'assumptions' => $evaluation['assumptions']];

            if ($evaluation['status'] === 'pass') {
                break;
            }
        }

        return $candidates;
    }

    /**
     * @return array{status: 'pass'|'fail'|'assumed', assumptions: array<int, array<string, mixed>>}
     */
    public function evaluateRules(mixed $rules, array $state): array
    {
        if (! is_array($rules) || $rules === []) {
            return ['status' => 'pass', 'assumptions' => []];
        }

        $assumptions = [];

        foreach ($rules as $key => $rule) {
            $value = is_array($rule) ? ($rule['value'] ?? null) : $rule;
            $value2 = is_array($rule) ? ($rule['value2'] ?? null) : null;
            $consume = is_array($rule) && (bool) ($rule['consume'] ?? false);

            if (in_array($key, self::QUEST_RULES, true)) {
                $result = $this->evaluateQuestRule($key, $value, $state);
                if ($result === false) {
                    return ['status' => 'fail', 'assumptions' => []];
                }
                if (is_array($result)) {
                    $assumptions[] = $result;
                }

                continue;
            }

            if ($key === 'items' || $key === 'equippedItems') {
                foreach (array_values(is_array($value) ? $value : [$value]) as $index => $itemId) {
                    if (! is_numeric($itemId)) {
                        continue;
                    }
                    $itemId = (int) $itemId;
                    $quantity = max(1, (int) (is_array($value2) ? ($value2[$index] ?? 1) : 1));

                    $owned = $state['items'][$itemId] ?? 0;
                    if ($owned >= $quantity) {
                        continue;
                    }

                    // A quest item the player has not received yet blocks the branch, unless it can also be found elsewhere.
                    if ($owned === 0 && isset($this->questItemIds[$itemId]) && ($this->data->itemSources[$itemId] ?? []) === []) {
                        return ['status' => 'fail', 'assumptions' => []];
                    }

                    $assumptions[] = [
                        'type' => $key === 'items' ? 'item' : 'equipped_item',
                        'item_id' => $itemId,
                        'quantity' => $quantity,
                        'consume' => $consume && $key === 'items',
                        'text' => $key === 'items'
                            ? ($consume ? 'Oddaj' : 'Miej przy sobie')." {$this->quantityLabel($quantity)}{$this->data->itemName($itemId)}"
                            : "Miej założony przedmiot {$this->data->itemName($itemId)}",
                    ];
                }

                continue;
            }

            $assumption = $this->describeAssumption((string) $key, $value, $value2, $consume);
            if ($assumption !== null) {
                $assumptions[] = $assumption;
            }
        }

        return ['status' => $assumptions === [] ? 'pass' : 'assumed', 'assumptions' => $assumptions];
    }

    /**
     * Follows an edge exactly like DialogService::processEdge.
     *
     * @return array<int, array> positions
     */
    public function traverse(array $edge, array $state, array $assumptions, array $timeline, int $clicks = 0, int $depth = 0): array
    {
        if ($depth > 30) {
            return [];
        }

        [$state, $timeline] = $this->consumeRules($edge['rules'] ?? [], $state, $timeline);
        if ($assumptions !== []) {
            $timeline[] = ['type' => 'condition', 'requirements' => $assumptions];
        }

        $node = $this->data->nodes[$edge['target_node_id']] ?? null;
        if ($node === null) {
            return [$this->end('close', $state, $timeline, $clicks)];
        }

        switch ($node['type']) {
            case 'randomizer':
                $edges = $this->data->edgesByNode[$node['id']] ?? [];
                $weighted = array_values(array_filter($edges, fn (array $e): bool => $this->percentage($e) > 0));
                $choices = $weighted !== [] ? $weighted : $edges;
                $positions = [];
                foreach ($choices as $choice) {
                    if ($choice['target_node_id'] === null) {
                        continue;
                    }
                    $chance = $weighted !== [] ? $this->percentage($choice) : (int) floor(100 / max(1, count($choices)));
                    $positions = [...$positions, ...$this->traverse($choice, $state, [[
                        'type' => 'random',
                        'chance' => $chance,
                        'text' => "Wynik losowy (szansa ok. {$chance}%)",
                    ]], $timeline, $clicks, $depth + 1)];
                }

                return $positions;

            case 'profession':
                $positions = [];
                foreach ($this->data->optionsByNode[$node['id']] ?? [] as $option) {
                    $code = strtolower(substr(trim((string) $option['label']), 0, 1));
                    $profession = self::PROFESSIONS[$code] ?? $option['label'];
                    foreach ($this->selectableEdges($this->data->edgesByOption[$option['id']] ?? [], $state) as $candidate) {
                        $positions = [...$positions, ...$this->traverse($candidate['edge'], $state, [
                            ['type' => 'profession', 'profession' => $code, 'text' => "Dotyczy profesji: {$profession}"],
                            ...$candidate['assumptions'],
                        ], $timeline, $clicks, $depth + 1)];
                    }
                }

                return $positions;

            case 'minigame':
                $minigame = data_get($node['action_data'], 'minigame.type', 'random');
                $positions = [];
                foreach ($this->data->edgesByNode[$node['id']] ?? [] as $minigameEdge) {
                    if (! str_contains((string) $minigameEdge['source_handle'], 'success')) {
                        continue;
                    }
                    $positions = [...$positions, ...$this->traverse($minigameEdge, $state, [[
                        'type' => 'minigame',
                        'text' => "Wygraj minigrę ({$minigame})",
                    ]], $timeline, $clicks, $depth + 1)];
                }

                return $positions;

            case 'shop':
            case 'hotel':
            case 'teleportation':
                [$state, $timeline] = $this->applyActions($node['additional_actions'], $state, $timeline);

                if ($node['type'] === 'teleportation') {
                    $teleportation = data_get($node['action_data'], 'teleportation');
                    $mapId = (int) data_get($teleportation, 'mapId', 0);
                    $timeline[] = [
                        'type' => 'teleport',
                        'map_id' => $mapId,
                        'map_name' => $this->data->mapName($mapId),
                        'x' => (int) data_get($teleportation, 'x', 0),
                        'y' => (int) data_get($teleportation, 'y', 0),
                        'instance' => (bool) data_get($teleportation, 'createInstance', false),
                    ];
                } else {
                    $timeline[] = $node['type'] === 'shop'
                        ? ['type' => 'shop', 'shop_id' => $node['shop_id']]
                        : ['type' => 'hotel', 'hotel_id' => $node['hotel_id']];
                }

                return [$this->end($node['type'], $state, $timeline, $clicks)];

            default:
                $options = [];
                foreach ($this->data->optionsByNode[$node['id']] ?? [] as $option) {
                    $evaluation = $this->evaluateRules($option['rules'], $state);
                    if ($evaluation['status'] !== 'fail') {
                        $options[] = ['option' => $option, 'requirements' => $evaluation['assumptions']];
                    }
                }

                $timeline[] = ['type' => 'npc', 'node_id' => $node['id'], 'text' => $this->excerpt($node['content'])];
                [$state, $timeline] = $this->applyActions($node['additional_actions'], $state, $timeline);

                return [[
                    'kind' => 'node',
                    'node_id' => $node['id'],
                    'state' => $state,
                    'options' => $options,
                    'timeline' => $timeline,
                    'clicks' => $clicks,
                ]];
        }
    }

    /**
     * Clicks a visible option, mirroring DialogService::executeOption.
     *
     * @return array<int, array> positions
     */
    public function click(array $position, array $visibleOption): array
    {
        $option = $visibleOption['option'];
        $state = $position['state'];
        $timeline = $position['timeline'];
        $clicks = $position['clicks'] + 1;

        $timeline[] = [
            'type' => 'choose',
            'option_id' => $option['id'],
            'label' => (string) ($option['label'] ?? ''),
            'requirements' => $visibleOption['requirements'],
        ];

        [$state, $timeline] = $this->consumeRules($option['rules'], $state, $timeline);
        [$state, $timeline] = $this->applyActions($option['additional_actions'], $state, $timeline);

        $action = $option['additional_action'];
        if ($action !== null) {
            $timeline[] = ['type' => 'option_action', 'action' => $action];

            if (in_array($action, self::TERMINATING_OPTION_ACTIONS, true)) {
                return [$this->end(strtolower($action), $state, $timeline, $clicks)];
            }
        }

        $candidates = $this->selectableEdges($this->data->edgesByOption[$option['id']] ?? [], $state);
        if ($candidates === []) {
            return [$this->end('close', $state, $timeline, $clicks)];
        }

        $positions = [];
        foreach ($candidates as $candidate) {
            $positions = [...$positions, ...$this->traverse($candidate['edge'], $state, $candidate['assumptions'], $timeline, $clicks)];
        }

        return $positions;
    }

    /**
     * Engine semantics of questStep / questBeforeStep / questAfterStep (RuleService::checkQuestWithComparator).
     *
     * @return bool|array<string, mixed> true, false, or an assumption about another quest
     */
    private function evaluateQuestRule(string $key, mixed $value, array $state): bool|array
    {
        $entries = is_array($value) ? $value : [$value];
        $own = [];
        $foreign = [];

        foreach ($entries as $entry) {
            if (! is_string($entry) || ! preg_match('/^([qs])-(\d+)$/', $entry, $matches)) {
                continue;
            }
            $id = (int) $matches[2];
            $belongs = $matches[1] === 'q' ? $id === $this->questId : isset($this->stepPositions[$id]);
            if ($belongs) {
                $own[] = [$matches[1], $id];
            } else {
                $foreign[] = [$matches[1], $id];
            }
        }

        $comparator = match ($key) {
            'questStep' => fn (int $current, int $target): bool => $current === $target,
            'questBeforeStep' => fn (int $current, int $target): bool => $current < $target,
            default => fn (int $current, int $target): bool => $current > $target,
        };

        if (is_array($value)) {
            // List semantics: quest ids are checked together, step ids individually.
            $questIds = array_merge(
                array_map(fn (array $e) => $e[1], array_filter($own, fn (array $e) => $e[0] === 'q')),
                array_map(fn (array $e) => $e[1], array_filter($foreign, fn (array $e) => $e[0] === 'q')),
            );
            if ($questIds !== []) {
                $present = fn (int $questId): bool => $questId === $this->questId && $state['quest_started'];
                $questCheck = $key === 'questStep'
                    ? array_reduce($questIds, fn (bool $carry, int $id) => $carry && $present($id), true)
                    : array_reduce($questIds, fn (bool $carry, int $id) => $carry && ! $present($id), true);
                if ($questCheck) {
                    return true;
                }
            }

            foreach ($own as [$prefix, $id]) {
                if ($prefix === 's' && $state['step'] !== null && $comparator($state['step'], $id)) {
                    return true;
                }
            }
        } elseif ($own !== []) {
            [$prefix, $id] = $own[0];
            if ($prefix === 'q') {
                return $key === 'questStep' ? $state['quest_started'] : ! $state['quest_started'];
            }

            return $state['step'] !== null && $comparator($state['step'], $id);
        } elseif ($foreign !== []) {
            [$prefix] = $foreign[0];
            // Pessimistic default: the player has no progress in other quests.
            if ($prefix === 'q' && $key !== 'questStep') {
                return true;
            }
        }

        if ($foreign !== [] && $this->assumeOtherQuests) {
            return [
                'type' => 'other_quest',
                'text' => $this->describeForeignQuestRule($key, $foreign),
            ];
        }

        return false;
    }

    private function describeForeignQuestRule(string $key, array $foreign): string
    {
        $labels = array_map(function (array $entry): string {
            [$prefix, $id] = $entry;
            if ($prefix === 'q') {
                return '„'.($this->data->quests[$id]['name'] ?? "Quest #{$id}").'”';
            }
            $questId = $this->data->questIdForStep($id);
            $stepName = collect($this->data->steps((int) $questId))->firstWhere('id', $id)['name'] ?? "krok #{$id}";
            $questName = $questId !== null ? ($this->data->quests[$questId]['name'] ?? "Quest #{$questId}") : 'inny quest';

            return "„{$questName}” (krok: {$stepName})";
        }, $foreign);

        $joined = implode(', ', $labels);

        return match ($key) {
            'questStep' => "Wymaga aktywnego etapu innego zadania: {$joined}",
            'questBeforeStep' => "Wymaga, by inne zadanie nie było jeszcze na tym etapie: {$joined}",
            default => "Wymaga postępu w innym zadaniu: {$joined}",
        };
    }

    private function describeAssumption(string $key, mixed $value, mixed $value2, bool $consume): ?array
    {
        $text = match ($key) {
            'gold' => ($consume ? 'Zapłać ' : 'Miej przy sobie ').$this->number($value).' złota',
            'honorPoints' => ($consume ? 'Wydaj ' : 'Miej ').$this->number($value).' punktów honoru',
            'dragonTears' => ($consume ? 'Wydaj ' : 'Miej ').$this->number($value).' smoczych łez',
            'level' => 'Wymagany poziom co najmniej '.$this->number($value),
            'levelBelow' => 'Poziom niższy niż '.$this->number($value),
            'brotherhood' => 'Wymagane członkostwo w Karmazynowym Bractwie',
            'percentageChance' => 'Szansa '.$this->number($value).'%',
            'messageContent' => 'Wpisz odpowiedź: „'.$value.'”',
            'dialogCounter' => $this->describeCounter($value, $value2),
            'seasonalEvent' => 'Tylko podczas wydarzenia: '.($this->data->seasonalEvents[(int) $value] ?? "#{$value}"),
            'timeAfter' => 'Dostępne od godziny '.$value,
            'timeBefore' => 'Dostępne przed godziną '.$value,
            'weekday' => 'Dostępne w dni: '.$this->weekdays($value),
            'activePlayersOnMap' => 'Na mapie musi być dokładnie '.$this->number($value).' aktywnych graczy',
            'hasActiveBlessing' => 'Wymagane aktywne błogosławieństwo',
            default => null,
        };

        if ($text === null) {
            return null;
        }

        return [
            'type' => $key,
            'value' => $value,
            'consume' => $consume,
            'text' => $text,
        ];
    }

    private function describeCounter(mixed $counterId, mixed $condition): string
    {
        [$operator, $target] = is_array($condition) ? [$condition[0] ?? '=', $condition[1] ?? 0] : ['=', 0];
        $name = $this->data->dialogCounters[(int) $counterId] ?? "Licznik #{$counterId}";

        return "Licznik „{$name}” {$operator} {$target}";
    }

    /**
     * Applies consumable rules (RuleService::processRules).
     */
    private function consumeRules(mixed $rules, array $state, array $timeline): array
    {
        if (! is_array($rules)) {
            return [$state, $timeline];
        }

        foreach ($rules as $key => $rule) {
            if (! in_array($key, self::CONSUMABLE_RULES, true) || ! is_array($rule) || ! ($rule['consume'] ?? false)) {
                continue;
            }

            if ($key === 'items') {
                $values = array_values(is_array($rule['value'] ?? null) ? $rule['value'] : [$rule['value'] ?? null]);
                $items = [];
                foreach ($values as $index => $itemId) {
                    if (! is_numeric($itemId)) {
                        continue;
                    }
                    $quantity = max(1, (int) (is_array($rule['value2'] ?? null) ? ($rule['value2'][$index] ?? 1) : 1));
                    $state['items'][(int) $itemId] = max(0, ($state['items'][(int) $itemId] ?? 0) - $quantity);
                    if ($state['items'][(int) $itemId] === 0) {
                        unset($state['items'][(int) $itemId]);
                    }
                    $items[] = ['item_id' => (int) $itemId, 'quantity' => $quantity];
                }
                if ($items !== []) {
                    $timeline[] = ['type' => 'cost_items', 'items' => $items];
                }

                continue;
            }

            $timeline[] = ['type' => 'cost_'.$this->snake($key), 'value' => (int) ($rule['value'] ?? 0)];
        }

        ksort($state['items']);

        return [$state, $timeline];
    }

    /**
     * Applies additional actions (DialogNodeAdditionalActionService).
     */
    private function applyActions(mixed $actions, array $state, array $timeline): array
    {
        if (! is_array($actions)) {
            return [$state, $timeline];
        }

        foreach ($actions as $key => $action) {
            $value = is_array($action) ? ($action['value'] ?? null) : $action;
            $value2 = is_array($action) ? ($action['value2'] ?? null) : null;

            switch ($key) {
                case 'setQuestStep':
                    if (! is_numeric($value)) {
                        break;
                    }
                    $stepId = (int) $value;
                    if (isset($this->stepPositions[$stepId])) {
                        $timeline[] = ['type' => 'step', 'from' => $state['step'], 'to' => $stepId, 'started' => ! $state['quest_started']];
                        $state['quest_started'] = true;
                        $state['step'] = $stepId;
                    }
                    break;

                case 'addItems':
                    $items = [];
                    foreach (array_values(is_array($value) ? $value : [$value]) as $index => $itemId) {
                        if (! is_numeric($itemId)) {
                            continue;
                        }
                        $quantity = max(1, (int) (is_array($value2) ? ($value2[$index] ?? 1) : 1));
                        $state['items'][(int) $itemId] = ($state['items'][(int) $itemId] ?? 0) + $quantity;
                        $items[] = ['item_id' => (int) $itemId, 'quantity' => $quantity];
                    }
                    if ($items !== []) {
                        $timeline[] = ['type' => 'reward_items', 'items' => $items];
                    }
                    break;

                case 'addGold':
                case 'addHonorPoints':
                case 'addExp':
                    $timeline[] = ['type' => 'reward_'.$this->snake(substr($key, 3)), 'value' => (int) $value];
                    break;

                case 'addExpPercent':
                    $timeline[] = ['type' => 'reward_exp_percent', 'value' => (float) $value];
                    break;

                case 'blessing':
                    if (is_numeric($value)) {
                        $timeline[] = ['type' => 'reward_blessing', 'item_id' => (int) $value];
                    }
                    break;

                case 'setOutfit':
                    $timeline[] = ['type' => 'reward_outfit', 'duration' => is_array($value) ? ($value['duration'] ?? null) : $value2];
                    break;

                case 'resetAdditionalAttributePoints':
                    $timeline[] = ['type' => 'reset_attribute_points'];
                    break;
            }
        }

        ksort($state['items']);

        return [$state, $timeline];
    }

    private function end(string $reason, array $state, array $timeline, int $clicks): array
    {
        return ['kind' => 'end', 'reason' => $reason, 'state' => $state, 'timeline' => $timeline, 'clicks' => $clicks];
    }

    private function registerQuestItems(mixed $actions): void
    {
        $value = data_get($actions, 'addItems.value');
        foreach (is_array($value) ? $value : [$value] as $itemId) {
            if (is_numeric($itemId)) {
                $this->questItemIds[(int) $itemId] = true;
            }
        }
    }

    private function percentage(array $edge): int
    {
        return (int) data_get($edge['rules'], 'percentageChance.value', 0);
    }

    private function excerpt(?string $content): ?string
    {
        if ($content === null || trim($content) === '') {
            return null;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', preg_replace('/\[[^\]]*\]/u', ' ', $content)));

        return mb_strlen($plain) > 280 ? mb_substr($plain, 0, 277).'…' : $plain;
    }

    private function quantityLabel(int $quantity): string
    {
        return $quantity > 1 ? "{$quantity}× " : '';
    }

    private function number(mixed $value): string
    {
        return is_numeric($value) ? number_format((float) $value, 0, ',', ' ') : (string) $value;
    }

    private function weekdays(mixed $value): string
    {
        $names = [1 => 'pon', 2 => 'wt', 3 => 'śr', 4 => 'czw', 5 => 'pt', 6 => 'sob', 7 => 'nd'];

        return implode(', ', array_map(fn ($day) => $names[(int) $day] ?? (string) $day, is_array($value) ? $value : [$value]));
    }

    private function snake(string $value): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }
}

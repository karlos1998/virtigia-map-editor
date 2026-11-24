<?php

namespace App\Services;

use App\Enums\Profession;
use DomainException;
use stdClass;

class DialogQuestAvailabilityCompiler
{
    public const FORMAT_VERSION = 3;

    public const REVISION = 2;

    private const TERMINATING_ACTIONS = [
        'BATTLE', 'KILL_AND_LOOT', 'KILL', 'SHOW_MAIL', 'SHOW_AUCTIONS',
        'SHOW_DEPOSIT', 'SHOW_CLAN_DEPOSIT', 'SELF_KILL',
    ];

    private const SUPPORTED_RULES = [
        'gold', 'honorPoints', 'level', 'levelBelow', 'brotherhood', 'items',
        'equippedItems', 'questStep', 'questBeforeStep', 'questAfterStep', 'dragonTears',
        'dialogCounter', 'seasonalEvent', 'timeAfter', 'timeBefore', 'weekday',
        'activePlayersOnMap', 'birthday', 'hasActiveBlessing', 'messageContent', 'percentageChance',
    ];

    /** @return list<int> */
    public function stepIds(?string $actions): array
    {
        $value = json_decode($actions ?? 'null')->setQuestStep->value ?? null;
        $ids = [];

        foreach (is_array($value) ? $value : [$value] as $reference) {
            if (is_int($reference) || is_float($reference)) {
                $ids[] = (int) $reference;
            } elseif (is_string($reference) && preg_match('/^s.[+-]?\d+$/D', $reference)) {
                $ids[] = (int) substr($reference, 2);
            }
        }

        return array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
    }

    /** @return array{requirements: ?array, fallback_reason: ?string} */
    public function compile(array $nodes, array $optionsByNode, array $startEdges, array $edgesByOption, array $edgesByNode, array $visibleQuestsByStep): array
    {
        $questReachableNodes = $this->findNodesThatCanReachQuest($nodes, $optionsByNode, $edgesByOption, $edgesByNode, $visibleQuestsByStep);

        $paths = [];
        $pending = [];
        $examined = 0;

        try {
            foreach ($startEdges as $edge) {
                if (isset($questReachableNodes[$edge->target_node_id]) || ! isset($nodes[$edge->target_node_id])) {
                    $pending[] = [(int) $edge->target_node_id, [$this->edgeCondition($edge)], []];
                }
            }

            while ($pending !== []) {
                [$nodeId, $conditions, $ancestors] = array_pop($pending);
                if (++$examined > 32768 || count($ancestors) > 256) {
                    throw new DomainException('graph_size');
                }
                if (isset($ancestors[$nodeId])) {
                    continue;
                }

                $node = $nodes[$nodeId] ?? throw new DomainException('external_node');
                $ancestors[$nodeId] = true;
                $branches = match ($node->type) {
                    'profession' => $this->professionBranches($optionsByNode[$nodeId] ?? [], $edgesByOption),
                    'randomizer' => $this->randomizerBranches($edgesByNode[$nodeId] ?? []),
                    'minigame' => array_map(
                        fn (stdClass $edge): array => [$edge, $this->edgeCondition($edge)],
                        $this->questReachableEdges($edgesByNode[$nodeId] ?? [], $questReachableNodes),
                    ),
                    default => null,
                };
                if ($branches !== null) {
                    foreach ($branches as [$edge, $condition]) {
                        if (isset($questReachableNodes[$edge->target_node_id])) {
                            $pending[] = [(int) $edge->target_node_id, [...$conditions, $condition], $ancestors];
                        }
                    }

                    continue;
                }

                $this->addPaths($paths, $node->additional_actions, $conditions, $visibleQuestsByStep);

                foreach ($optionsByNode[$nodeId] ?? [] as $option) {
                    $optionEdges = $this->isTerminatingOption($option) ? []
                        : $this->questReachableEdges($edgesByOption[$option->id] ?? [], $questReachableNodes);
                    if ($optionEdges === [] && $this->startingQuests($option->additional_actions, $visibleQuestsByStep) === []) {
                        continue;
                    }

                    $optionConditions = [...$conditions, ['optionId' => (int) $option->id, 'check' => $this->ruleCheck($this->rules($option->rules))]];
                    $this->addPaths($paths, $option->additional_actions, $optionConditions, $visibleQuestsByStep);

                    foreach ($optionEdges as $edge) {
                        $pending[] = [(int) $edge->target_node_id, [...$optionConditions, $this->edgeCondition($edge, optionEdge: true)], $ancestors];
                    }
                }
            }
        } catch (DomainException $exception) {
            return ['requirements' => null, 'fallback_reason' => $exception->getMessage()];
        }

        return ['requirements' => array_values($paths), 'fallback_reason' => null];
    }

    private function findNodesThatCanReachQuest(array $nodes, array $optionsByNode, array $edgesByOption, array $edgesByNode, array $visibleQuestsByStep): array
    {
        $predecessors = [];
        $reachable = [];
        foreach ($nodes as $nodeId => $node) {
            if (in_array($node->type, ['randomizer', 'minigame'], true)) {
                $edges = $edgesByNode[$nodeId] ?? [];
            } else {
                $edges = [];
                if ($node->type !== 'profession' && $this->startingQuests($node->additional_actions, $visibleQuestsByStep) !== []) {
                    $reachable[$nodeId] = true;
                }

                foreach ($optionsByNode[$nodeId] ?? [] as $option) {
                    if ($node->type === 'profession') {
                        $edges = [...$edges, ...($edgesByOption[$option->id] ?? [])];

                        continue;
                    }
                    if ($this->startingQuests($option->additional_actions, $visibleQuestsByStep) !== []) {
                        $reachable[$nodeId] = true;
                    }
                    if (! $this->isTerminatingOption($option)) {
                        $edges = [...$edges, ...($edgesByOption[$option->id] ?? [])];
                    }
                }
            }

            foreach ($edges as $edge) {
                $target = (int) $edge->target_node_id;
                $predecessors[$target][] = $nodeId;
                if (! isset($nodes[$target])) {
                    $reachable[$target] = true;
                }
            }
        }

        $pending = array_keys($reachable);
        while ($pending !== []) {
            foreach ($predecessors[array_pop($pending)] ?? [] as $source) {
                if (! isset($reachable[$source])) {
                    $reachable[$source] = true;
                    $pending[] = $source;
                }
            }
        }

        return $reachable;
    }

    private function questReachableEdges(array $edges, array $questReachableNodes): array
    {
        return array_filter($edges, static fn (stdClass $edge): bool => isset($questReachableNodes[$edge->target_node_id]));
    }

    private function isTerminatingOption(stdClass $option): bool
    {
        return in_array(strtoupper($option->additional_action ?? ''), self::TERMINATING_ACTIONS, true);
    }

    /** @return list<int> */
    private function startingQuests(?string $actions, array $visibleQuestsByStep): array
    {
        $questIds = [];
        foreach ($this->stepIds($actions) as $stepId) {
            if (isset($visibleQuestsByStep[$stepId])) {
                $questIds[] = $visibleQuestsByStep[$stepId];
            }
        }

        return $questIds;
    }

    private function professionBranches(array $options, array $edgesByOption): array
    {
        $branches = [];
        $remainingProfessions = array_fill_keys(Profession::valuesToList(), true);

        foreach ($options as $option) {
            $label = strtolower(trim($option->label ?? '', "\x00..\x20"));
            $profession = $label[0] ?? '';
            if (! isset($remainingProfessions[$profession])) {
                continue;
            }
            unset($remainingProfessions[$profession]);

            $edges = $edgesByOption[$option->id] ?? [];
            usort($edges, fn (stdClass $left, stdClass $right): int => count($this->rules($right->rules)) <=> count($this->rules($left->rules)) ?: $left->id <=> $right->id);
            $earlierChecks = [];
            foreach ($edges as $edge) {
                $check = $this->ruleCheck($this->rules($edge->rules));
                $branches[] = [$edge, ['profession' => $profession, 'check' => $check, 'unless' => $earlierChecks]];

                if ((array) $check['rules'] === []) {
                    break;
                }
                $earlierChecks[] = $check;
            }
        }

        return $branches;
    }

    private function randomizerBranches(array $edges): array
    {
        $weighted = [];
        $unweighted = [];
        foreach ($edges as $edge) {
            $rules = $this->rules($edge->rules);
            $weight = $rules['percentageChance']->value ?? null;
            unset($rules['percentageChance']);
            $check = $this->ruleCheck($rules);

            if ($this->isPositiveWeight($weight)) {
                $weighted[] = [$edge, $check];
            } else {
                $unweighted[] = [$edge, $check];
            }
        }

        $weightedChecks = array_column($weighted, 1);
        $branches = array_map(static fn (array $branch): array => [$branch[0], ['check' => $branch[1]]], $weighted);

        foreach ($weightedChecks as $check) {
            if ((array) $check['rules'] === []) {
                return $branches;
            }
        }

        foreach ($unweighted as [$edge, $check]) {
            $branches[] = [$edge, ['check' => $check, 'unless' => $weightedChecks]];
        }

        return $branches;
    }

    private function isPositiveWeight(mixed $weight): bool
    {
        if (is_int($weight)) {
            // Java narrows Long to int before testing the sign.
            $unsigned = $weight & 0xFFFFFFFF;

            return $unsigned > 0 && $unsigned <= 0x7FFFFFFF;
        }

        return is_float($weight) && $weight >= 1;
    }

    private function edgeCondition(stdClass $edge, bool $optionEdge = false): array
    {
        return ['check' => $this->ruleCheck($this->rules($edge->rules), $optionEdge)];
    }

    private function rules(?string $json): array
    {
        $rules = json_decode($json ?? '[]');
        if (! is_object($rules) && $rules !== [] && $json !== 'null') {
            throw new DomainException('invalid_rules');
        }

        return (array) $rules;
    }

    private function ruleCheck(array $rules, bool $optionEdge = false): array
    {
        foreach ($rules as $name => $rule) {
            if (! in_array($name, self::SUPPORTED_RULES, true) || ! is_object($rule)) {
                throw new DomainException('rule:'.$name);
            }
        }

        $check = ['rules' => (object) $rules];
        if ($optionEdge && isset($rules['messageContent'])) {
            $check['optionEdge'] = true;
        }

        return $check;
    }

    private function addPaths(array &$paths, ?string $actions, array $conditions, array $visibleQuestsByStep): void
    {
        $conditions = array_values(array_filter(
            $conditions,
            static fn (array $condition): bool => isset($condition['optionId']) || isset($condition['profession'])
                || ! empty($condition['unless']) || (array) $condition['check']['rules'] !== [],
        ));

        foreach ($this->startingQuests($actions, $visibleQuestsByStep) as $questId) {
            $path = ['questId' => $questId, 'conditions' => $conditions];
            $paths[json_encode($path, JSON_THROW_ON_ERROR)] = $path;
            if (count($paths) > 2048) {
                throw new DomainException('path_count');
            }
        }
    }
}

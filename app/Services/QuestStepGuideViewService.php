<?php

namespace App\Services;

use App\Models\BaseItem;
use App\Models\BaseItemUsageView;
use App\Models\Dialog;
use App\Models\DialogCounter;
use App\Models\DialogNode;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\QuestStepGuideView;
use Illuminate\Support\Collection;

class QuestStepGuideViewService
{
    public function clear(string $connection): void
    {
        QuestStepGuideView::on($connection)->delete();
    }

    public function refreshChunk(string $connection, int $chunkIndex, int $chunkSize): void
    {
        $questSteps = QuestStep::on($connection)
            ->with('quest')
            ->orderBy('id')
            ->skip($chunkIndex * $chunkSize)
            ->take($chunkSize)
            ->get();

        if ($questSteps->isEmpty()) {
            return;
        }

        $triggerNodes = DialogNode::on($connection)
            ->whereNotNull('additional_actions')
            ->get()
            ->filter(function (DialogNode $node) use ($questSteps): bool {
                $value = data_get($node->additional_actions, 'setQuestStep.value');

                return is_numeric($value) && $questSteps->pluck('id')->contains((int) $value);
            })
            ->values();

        $dialogs = $this->loadRelevantDialogs($connection, $triggerNodes);
        $payload = $questSteps->map(function (QuestStep $questStep) use ($dialogs, $triggerNodes, $connection): array {
            $guides = $this->buildGuidesForQuestStep(
                $connection,
                $questStep,
                $triggerNodes->where(fn (DialogNode $node) => (int) data_get($node->additional_actions, 'setQuestStep.value') === $questStep->id)->values(),
                $dialogs
            );

            return [
                'quest_step_id' => $questStep->id,
                'guide_count' => count($guides),
                'guides' => empty($guides) ? null : json_encode($guides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        foreach ($payload->chunk(200) as $chunk) {
            QuestStepGuideView::on($connection)->upsert(
                $chunk->all(),
                ['quest_step_id'],
                ['guide_count', 'guides', 'updated_at']
            );
        }
    }

    public function describeRules(
        string $connection,
        ?array $rules,
        Collection $itemsById,
        Collection $itemUsageById,
        Collection $questsById,
        Collection $questStepsById,
        Collection $dialogCountersById,
    ): array {
        if (! is_array($rules) || empty($rules)) {
            return [];
        }

        $descriptions = [];

        foreach ($rules as $key => $ruleData) {
            $value = $ruleData['value'] ?? null;
            $consume = (bool) ($ruleData['consume'] ?? false);

            if ($key === 'items' && is_array($value)) {
                foreach ($value as $itemId) {
                    $item = $itemsById->get((int) $itemId);
                    $usageView = $itemUsageById->get((int) $itemId);
                    $itemName = $item?->name ?? "Przedmiot #{$itemId}";

                    $descriptions[] = [
                        'type' => 'item',
                        'text' => ($consume ? 'Zużyj lub oddaj' : 'Miej przy sobie')." {$itemName} (#{$itemId})",
                        'consume' => $consume,
                        'item' => [
                            'id' => (int) $itemId,
                            'name' => $itemName,
                            'src' => $item
                                ? config('assets.url').config('assets.dirs.items').$item->src.'?'.$item->updated_at->timestamp
                                : null,
                            'usage_sources' => $usageView?->sources ?? [],
                        ],
                    ];
                }

                continue;
            }

            if ($key === 'gold' && is_numeric($value)) {
                $descriptions[] = [
                    'type' => 'gold',
                    'text' => ($consume ? 'Zapłać' : 'Miej przy sobie')." {$value} złota",
                ];

                continue;
            }

            if ($key === 'level' && is_numeric($value)) {
                $descriptions[] = [
                    'type' => 'level',
                    'text' => "Wymagany poziom: {$value}+",
                ];

                continue;
            }

            if ($key === 'dragonTears' && is_numeric($value)) {
                $descriptions[] = [
                    'type' => 'dragon_tears',
                    'text' => ($consume ? 'Wydaj' : 'Miej')." {$value} smoczych łez",
                ];

                continue;
            }

            if ($key === 'brotherhood' && is_numeric($value)) {
                $descriptions[] = [
                    'type' => 'brotherhood',
                    'text' => "Wymagane Karmazynowe Bractwo: {$value}",
                ];

                continue;
            }

            if ($key === 'messageContent' && is_string($value)) {
                $descriptions[] = [
                    'type' => 'message',
                    'text' => "W treści odpowiedzi musi pojawić się: \"{$value}\"",
                ];

                continue;
            }

            if ($key === 'dialogCounter' && is_numeric($value)) {
                $counter = $dialogCountersById->get((int) $value);
                [$operator, $counterValue] = $ruleData['value2'] ?? ['=', 0];
                $counterName = $counter?->name ?? "Licznik #{$value}";

                $descriptions[] = [
                    'type' => 'dialog_counter',
                    'text' => "{$counterName} musi spełniać warunek {$operator} {$counterValue}",
                ];

                continue;
            }

            if (in_array($key, ['questStep', 'questBeforeStep', 'questAfterStep'], true)) {
                foreach (is_array($value) ? $value : [$value] as $entry) {
                    $descriptions[] = [
                        'type' => 'quest',
                        'text' => $this->describeQuestRuleEntry($key, $entry, $questsById, $questStepsById),
                    ];
                }
            }
        }

        return $descriptions;
    }

    public function findShortestPath(Dialog $dialog, int $targetNodeId): array
    {
        $startNode = $dialog->nodes->firstWhere('type', 'start');

        if (! $startNode) {
            return [];
        }

        $queue = collect([[
            'node_id' => $startNode->id,
            'path' => [],
        ]]);
        $visited = [$startNode->id => true];

        while ($queue->isNotEmpty()) {
            $current = $queue->shift();
            $node = $dialog->nodes->firstWhere('id', $current['node_id']);

            if (! $node) {
                continue;
            }

            if ($node->id === $targetNodeId) {
                return $current['path'];
            }

            if (in_array($node->type, ['start', 'randomizer'], true)) {
                $edges = $dialog->edges->where('source_node_id', $node->id);

                foreach ($edges as $edge) {
                    if (! $edge->target_node_id || isset($visited[$edge->target_node_id])) {
                        continue;
                    }

                    $visited[$edge->target_node_id] = true;
                    $queue->push([
                        'node_id' => $edge->target_node_id,
                        'path' => [
                            ...$current['path'],
                            [
                                'type' => 'auto',
                                'node' => $this->serializeNode($node),
                                'to_node' => $this->serializeNode($dialog->nodes->firstWhere('id', $edge->target_node_id)),
                                'edge_rules' => $edge->rules ?? [],
                            ],
                        ],
                    ]);
                }

                continue;
            }

            if ($node->type !== 'special') {
                continue;
            }

            foreach ($node->options as $option) {
                foreach ($option->edges as $edge) {
                    if (! $edge->target_node_id || isset($visited[$edge->target_node_id])) {
                        continue;
                    }

                    $visited[$edge->target_node_id] = true;
                    $queue->push([
                        'node_id' => $edge->target_node_id,
                        'path' => [
                            ...$current['path'],
                            [
                                'type' => 'option',
                                'node' => $this->serializeNode($node),
                                'option' => [
                                    'id' => $option->id,
                                    'label' => $option->label,
                                    'rules' => $option->rules ?? [],
                                ],
                                'to_node' => $this->serializeNode($dialog->nodes->firstWhere('id', $edge->target_node_id)),
                                'edge_rules' => $edge->rules ?? [],
                            ],
                        ],
                    ]);
                }
            }
        }

        return [];
    }

    private function buildGuidesForQuestStep(
        string $connection,
        QuestStep $questStep,
        Collection $triggerNodes,
        Collection $dialogs,
    ): array {
        if ($triggerNodes->isEmpty()) {
            return [];
        }

        $requiredItemIds = [];
        $questRuleRefs = [];
        $dialogCounterIds = [];

        foreach ($triggerNodes as $triggerNode) {
            $dialog = $dialogs->get($triggerNode->source_dialog_id);
            if (! $dialog) {
                continue;
            }

            foreach ($this->findShortestPath($dialog, $triggerNode->id) as $pathStep) {
                $requiredItemIds = [...$requiredItemIds, ...$this->extractItemIdsFromPayload($pathStep['option']['rules'] ?? [])];
                $requiredItemIds = [...$requiredItemIds, ...$this->extractItemIdsFromPayload($pathStep['edge_rules'] ?? [])];
                $questRuleRefs = [...$questRuleRefs, ...$this->extractQuestRefsFromRules($pathStep['option']['rules'] ?? [])];
                $questRuleRefs = [...$questRuleRefs, ...$this->extractQuestRefsFromRules($pathStep['edge_rules'] ?? [])];
                $dialogCounterIds = [...$dialogCounterIds, ...$this->extractDialogCounterIdsFromRules($pathStep['option']['rules'] ?? [])];
                $dialogCounterIds = [...$dialogCounterIds, ...$this->extractDialogCounterIdsFromRules($pathStep['edge_rules'] ?? [])];
            }
        }

        $itemsById = BaseItem::on($connection)
            ->whereIn('id', collect($requiredItemIds)->unique()->values())
            ->get()
            ->keyBy('id');
        $itemUsageById = BaseItemUsageView::on($connection)
            ->whereIn('base_item_id', collect($requiredItemIds)->unique()->values())
            ->get()
            ->keyBy('base_item_id');
        $questsById = Quest::on($connection)
            ->whereIn('id', collect($questRuleRefs)->filter(fn ($entry) => str_starts_with($entry, 'q-'))->map(fn ($entry) => (int) substr($entry, 2))->unique()->values())
            ->get()
            ->keyBy('id');
        $questStepsById = QuestStep::on($connection)
            ->with('quest')
            ->whereIn('id', collect($questRuleRefs)->filter(fn ($entry) => str_starts_with($entry, 's-'))->map(fn ($entry) => (int) substr($entry, 2))->unique()->values())
            ->get()
            ->keyBy('id');
        $dialogCountersById = DialogCounter::on($connection)
            ->whereIn('id', collect($dialogCounterIds)->unique()->values())
            ->get()
            ->keyBy('id');

        return $triggerNodes
            ->map(function (DialogNode $triggerNode) use (
                $connection,
                $dialogs,
                $dialogCountersById,
                $itemUsageById,
                $itemsById,
                $questStep,
                $questStepsById,
                $questsById,
            ): ?array {
                $dialog = $dialogs->get($triggerNode->source_dialog_id);

                if (! $dialog) {
                    return null;
                }

                $path = $this->findShortestPath($dialog, $triggerNode->id);
                $clickSteps = collect($path)
                    ->reject(fn (array $pathStep, int $index): bool => $index === 0 && ($pathStep['type'] ?? null) === 'auto' && data_get($pathStep, 'node.type') === 'start')
                    ->map(function (array $pathStep) use ($connection, $dialogCountersById, $itemUsageById, $itemsById, $questStepsById, $questsById): array {
                        return [
                            ...$pathStep,
                            'option_requirements' => $this->describeRules(
                                $connection,
                                $pathStep['option']['rules'] ?? [],
                                $itemsById,
                                $itemUsageById,
                                $questsById,
                                $questStepsById,
                                $dialogCountersById
                            ),
                            'edge_requirements' => $this->describeRules(
                                $connection,
                                $pathStep['edge_rules'] ?? [],
                                $itemsById,
                                $itemUsageById,
                                $questsById,
                                $questStepsById,
                                $dialogCountersById
                            ),
                        ];
                    })->values()->all();

                return [
                    'dialog' => [
                        'id' => $dialog->id,
                        'name' => $dialog->name,
                    ],
                    'target_node' => $this->serializeNode($triggerNode),
                    'step' => [
                        'id' => $questStep->id,
                        'name' => $questStep->name,
                    ],
                    'npcs' => $dialog->npcs->map(function ($npc): array {
                        return [
                            'id' => $npc->id,
                            'base_npc_id' => $npc->base?->id,
                            'name' => $npc->base?->name ?? "NPC #{$npc->id}",
                            'src' => $npc->base?->src
                                ? config('assets.url').config('assets.dirs.npcs').$npc->base->src
                                : null,
                            'locations' => $npc->locations->map(function ($location): array {
                                $mapName = $location->map?->name ?? "Mapa #{$location->map_id}";

                                return [
                                    'id' => $location->id,
                                    'map_id' => $location->map_id,
                                    'map_name' => $mapName,
                                    'x' => $location->x,
                                    'y' => $location->y,
                                    'label' => "{$mapName} ({$location->map_id}) x: {$location->x}, y: {$location->y}",
                                ];
                            })->values()->all(),
                        ];
                    })->values()->all(),
                    'click_steps' => $clickSteps,
                    'starts_on_dialog_open' => count($clickSteps) === 0,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function loadRelevantDialogs(string $connection, Collection $triggerNodes): Collection
    {
        $dialogIds = $triggerNodes->pluck('source_dialog_id')->unique()->values();

        return Dialog::on($connection)
            ->with([
                'npcs.base',
                'npcs.locations.map',
                'nodes.options.edges',
                'edges',
            ])
            ->whereIn('id', $dialogIds)
            ->get()
            ->keyBy('id');
    }

    private function serializeNode(?DialogNode $node): ?array
    {
        if (! $node) {
            return null;
        }

        return [
            'id' => $node->id,
            'type' => $node->type,
            'content' => $node->content,
        ];
    }

    private function describeQuestRuleEntry(
        string $key,
        mixed $entry,
        Collection $questsById,
        Collection $questStepsById,
    ): string {
        if (! is_string($entry) || ! str_contains($entry, '-')) {
            return 'Warunek questowy';
        }

        [$prefix, $id] = explode('-', $entry, 2);
        $id = (int) $id;

        if ($prefix === 'q') {
            $quest = $questsById->get($id);
            $name = $quest?->name ?? "Quest #{$id}";

            return match ($key) {
                'questBeforeStep' => "Quest przed rozpoczęciem lub przed krokiem: {$name}",
                'questAfterStep' => "Quest rozpoczęty lub po kroku: {$name}",
                default => "Aktywny quest: {$name}",
            };
        }

        $step = $questStepsById->get($id);
        $name = $step?->name ?? "Krok #{$id}";
        $questName = $step?->quest?->name;
        $suffix = $questName ? " ({$questName})" : '';

        return match ($key) {
            'questBeforeStep' => "Quest przed krokiem: {$name}{$suffix}",
            'questAfterStep' => "Quest po kroku: {$name}{$suffix}",
            default => "Wymagany krok questa: {$name}{$suffix}",
        };
    }

    private function extractItemIdsFromPayload(mixed $payload): array
    {
        $itemIds = [];

        if (! is_array($payload)) {
            return [];
        }

        foreach ($payload as $key => $ruleData) {
            if ($key !== 'items') {
                continue;
            }

            $value = $ruleData['value'] ?? [];
            if (! is_array($value)) {
                continue;
            }

            foreach ($value as $itemId) {
                if (is_numeric($itemId)) {
                    $itemIds[] = (int) $itemId;
                }
            }
        }

        return $itemIds;
    }

    private function extractQuestRefsFromRules(mixed $rules): array
    {
        $refs = [];

        if (! is_array($rules)) {
            return [];
        }

        foreach (['questStep', 'questBeforeStep', 'questAfterStep'] as $key) {
            $value = data_get($rules, "{$key}.value");

            if (is_string($value)) {
                $refs[] = $value;
            }

            if (is_array($value)) {
                foreach ($value as $entry) {
                    if (is_string($entry)) {
                        $refs[] = $entry;
                    }
                }
            }
        }

        return $refs;
    }

    private function extractDialogCounterIdsFromRules(mixed $rules): array
    {
        $counterId = data_get($rules, 'dialogCounter.value');

        return is_numeric($counterId) ? [(int) $counterId] : [];
    }
}

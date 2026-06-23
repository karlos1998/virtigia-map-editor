<?php

namespace App\Services;

use App\Models\Dialog;
use App\Models\DialogEdge;
use App\Models\DialogNode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DialogLayoutService
{
    private const DEFAULT_HORIZONTAL_GAP = 220;

    private const DEFAULT_VERTICAL_GAP = 80;

    /**
     * @return array<int, array{x: int, y: int, width: int, height: int, depth: int}>
     */
    public function calculate(
        Dialog $dialog,
        int $horizontalGap = self::DEFAULT_HORIZONTAL_GAP,
        int $verticalGap = self::DEFAULT_VERTICAL_GAP,
        int $startX = 0,
        int $startY = 0,
    ): array {
        $dialog->loadMissing(['nodes.options', 'edges']);

        /** @var Collection<int, DialogNode> $nodes */
        $nodes = $dialog->nodes->sortBy('id')->values();

        if ($nodes->isEmpty()) {
            return [];
        }

        /** @var Collection<int, DialogNode> $nodeById */
        $nodeById = $nodes->keyBy(fn (DialogNode $node): int => (int) $node->id);
        $startNodeId = $nodes->firstWhere('type', 'start')?->id;

        [$outgoing, $incoming] = $this->buildGraph($dialog, $nodes, $nodeById, $startNodeId ? (int) $startNodeId : null);

        $sizes = [];
        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;
            $sizes[$nodeId] = $this->estimateNodeSize($node, count($outgoing[$nodeId] ?? []));
        }

        [$depths, $order] = $this->assignDepths($nodes, $outgoing, $incoming, $startNodeId ? (int) $startNodeId : null);
        $layers = $this->buildLayers($nodes, $depths, $order);
        $layerX = $this->calculateLayerPositions($layers, $sizes, $horizontalGap, $startX);

        $positions = [];
        foreach ($layers as $depth => $nodeIds) {
            $currentY = $startY;

            foreach ($nodeIds as $nodeId) {
                $positions[$nodeId] = [
                    'x' => $layerX[$depth],
                    'y' => $currentY,
                    'width' => $sizes[$nodeId]['width'],
                    'height' => $sizes[$nodeId]['height'],
                    'depth' => $depth,
                ];

                $currentY += $sizes[$nodeId]['height'] + $verticalGap;
            }
        }

        return $positions;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function estimateNodeSize(DialogNode $node, int $outgoingEdgeCount = 0): array
    {
        $type = $node->type ?: 'special';

        return match ($type) {
            'shop' => ['width' => 269, 'height' => 435],
            'hotel' => ['width' => 170, 'height' => 180],
            'teleportation' => [
                'width' => 318,
                'height' => $this->isInstanceTeleport($node) ? 254 : 206,
            ],
            'profession' => [
                'width' => 336,
                'height' => max(196, 80 + ($this->optionsFor($node)->count() * 42)),
            ],
            'randomizer' => [
                'width' => 336,
                'height' => max(196, 110 + (max(1, $outgoingEdgeCount) * 86)),
            ],
            'minigame' => ['width' => 336, 'height' => 336],
            'start' => ['width' => 336, 'height' => 212],
            default => $this->estimateSpecialNodeSize($node),
        };
    }

    /**
     * @param  array<int, array{x: int, y: int, width: int, height: int, depth: int}>  $positions
     */
    public function save(Dialog $dialog, array $positions): void
    {
        $connection = $dialog->getConnectionName() ?? config('database.default');

        DB::connection($connection)->transaction(function () use ($connection, $positions): void {
            foreach ($positions as $nodeId => $position) {
                DialogNode::on($connection)
                    ->whereKey($nodeId)
                    ->update([
                        'position' => [
                            'x' => $position['x'],
                            'y' => $position['y'],
                        ],
                    ]);
            }
        });
    }

    /**
     * @param  Collection<int, DialogNode>  $nodes
     * @param  Collection<int, DialogNode>  $nodeById
     * @return array{0: array<int, list<array{target_id: int, sort: int}>>, 1: array<int, list<int>>}
     */
    private function buildGraph(Dialog $dialog, Collection $nodes, Collection $nodeById, ?int $startNodeId): array
    {
        $optionSources = $this->buildOptionSources($nodes);
        $outgoing = [];
        $incoming = [];

        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;
            $outgoing[$nodeId] = [];
            $incoming[$nodeId] = [];
        }

        foreach ($dialog->edges as $edge) {
            if (! $edge instanceof DialogEdge) {
                continue;
            }

            $targetNodeId = (int) $edge->target_node_id;
            if (! $nodeById->has($targetNodeId)) {
                continue;
            }

            $source = $this->resolveEdgeSource($edge, $optionSources, $startNodeId);
            if ($source === null || ! $nodeById->has($source['node_id'])) {
                continue;
            }

            $outgoing[$source['node_id']][] = [
                'target_id' => $targetNodeId,
                'sort' => $source['sort'],
            ];

            $incoming[$targetNodeId][] = $source['node_id'];
        }

        foreach ($outgoing as $nodeId => $edges) {
            usort($edges, function (array $left, array $right): int {
                return [$left['sort'], $left['target_id']] <=> [$right['sort'], $right['target_id']];
            });

            $outgoing[$nodeId] = $edges;
        }

        return [$outgoing, $incoming];
    }

    /**
     * @param  Collection<int, DialogNode>  $nodes
     * @return array<int, array{node_id: int, sort: int}>
     */
    private function buildOptionSources(Collection $nodes): array
    {
        $optionSources = [];

        foreach ($nodes as $node) {
            foreach ($this->optionsFor($node)->values() as $index => $option) {
                $optionId = (int) $option->id;
                $order = is_numeric($option->order) ? (int) $option->order : $index;

                $optionSources[$optionId] = [
                    'node_id' => (int) $node->id,
                    'sort' => ($order * 1000) + $optionId,
                ];
            }
        }

        return $optionSources;
    }

    /**
     * @param  array<int, array{node_id: int, sort: int}>  $optionSources
     * @return array{node_id: int, sort: int}|null
     */
    private function resolveEdgeSource(DialogEdge $edge, array $optionSources, ?int $startNodeId): ?array
    {
        if ($edge->source_option_id !== null) {
            return $optionSources[(int) $edge->source_option_id] ?? null;
        }

        if ($edge->source_node_id !== null) {
            return [
                'node_id' => (int) $edge->source_node_id,
                'sort' => $this->directHandleSort($edge->source_handle),
            ];
        }

        if ($startNodeId === null) {
            return null;
        }

        return [
            'node_id' => $startNodeId,
            'sort' => $this->directHandleSort($edge->source_handle),
        ];
    }

    private function directHandleSort(?string $sourceHandle): int
    {
        return match ($sourceHandle) {
            'source-success' => 0,
            'source-fail' => 1,
            default => 0,
        };
    }

    /**
     * @param  Collection<int, DialogNode>  $nodes
     * @param  array<int, list<array{target_id: int, sort: int}>>  $outgoing
     * @param  array<int, list<int>>  $incoming
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private function assignDepths(Collection $nodes, array $outgoing, array $incoming, ?int $startNodeId): array
    {
        $roots = $this->findRoots($nodes, $incoming, $startNodeId);
        $depths = [];
        $order = [];
        $visited = [];
        $visiting = [];
        $sequence = 0;

        $visit = function (int $nodeId, int $depth) use (&$visit, &$depths, &$order, &$visited, &$visiting, &$sequence, $outgoing): void {
            if (isset($visiting[$nodeId])) {
                return;
            }

            $depths[$nodeId] = max($depths[$nodeId] ?? 0, $depth);

            if (! isset($visited[$nodeId])) {
                $visited[$nodeId] = true;
                $order[$nodeId] = $sequence++;
            }

            $visiting[$nodeId] = true;

            foreach ($outgoing[$nodeId] ?? [] as $edge) {
                if ($edge['target_id'] === $nodeId) {
                    continue;
                }

                $visit($edge['target_id'], $depth + 1);
            }

            unset($visiting[$nodeId]);
        };

        foreach ($roots as $rootNodeId) {
            $visit($rootNodeId, 0);
        }

        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;

            if (! isset($visited[$nodeId])) {
                $visit($nodeId, 0);
            }
        }

        return [$depths, $order];
    }

    /**
     * @param  Collection<int, DialogNode>  $nodes
     * @param  array<int, list<int>>  $incoming
     * @return list<int>
     */
    private function findRoots(Collection $nodes, array $incoming, ?int $startNodeId): array
    {
        $roots = [];

        if ($startNodeId !== null) {
            $roots[] = $startNodeId;
        }

        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;

            if ($nodeId !== $startNodeId && count($incoming[$nodeId] ?? []) === 0) {
                $roots[] = $nodeId;
            }
        }

        return array_values(array_unique($roots));
    }

    /**
     * @param  Collection<int, DialogNode>  $nodes
     * @param  array<int, int>  $depths
     * @param  array<int, int>  $order
     * @return array<int, list<int>>
     */
    private function buildLayers(Collection $nodes, array $depths, array $order): array
    {
        $layers = [];

        foreach ($nodes as $node) {
            $nodeId = (int) $node->id;
            $depth = $depths[$nodeId] ?? 0;
            $layers[$depth][] = $nodeId;
        }

        ksort($layers);

        foreach ($layers as $depth => $nodeIds) {
            usort($nodeIds, function (int $left, int $right) use ($order): int {
                return [$order[$left] ?? PHP_INT_MAX, $left] <=> [$order[$right] ?? PHP_INT_MAX, $right];
            });

            $layers[$depth] = $nodeIds;
        }

        return $layers;
    }

    /**
     * @param  array<int, list<int>>  $layers
     * @param  array<int, array{width: int, height: int}>  $sizes
     * @return array<int, int>
     */
    private function calculateLayerPositions(array $layers, array $sizes, int $horizontalGap, int $startX): array
    {
        $layerX = [];
        $currentX = $startX;
        $previousMaxWidth = 0;

        foreach ($layers as $depth => $nodeIds) {
            if ($depth === array_key_first($layers)) {
                $layerX[$depth] = $currentX;
            } else {
                $currentX += $previousMaxWidth + $horizontalGap;
                $layerX[$depth] = $currentX;
            }

            $previousMaxWidth = max(array_map(fn (int $nodeId): int => $sizes[$nodeId]['width'], $nodeIds));
        }

        return $layerX;
    }

    /**
     * @return array{width: int, height: int}
     */
    private function estimateSpecialNodeSize(DialogNode $node): array
    {
        $optionRows = $this->optionsFor($node)->count() + 1;
        $contentLines = $this->estimateWrappedLines((string) $node->content, 52);

        return [
            'width' => 336,
            'height' => max(196, 50 + ($contentLines * 20) + ($optionRows * 38)),
        ];
    }

    private function estimateWrappedLines(string $text, int $charactersPerLine): int
    {
        if ($text === '') {
            return 1;
        }

        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [''];

        return array_reduce($lines, function (int $carry, string $line) use ($charactersPerLine): int {
            return $carry + max(1, (int) ceil(strlen($line) / $charactersPerLine));
        }, 0);
    }

    private function isInstanceTeleport(DialogNode $node): bool
    {
        return (bool) data_get($node->action_data, 'teleportation.createInstance', false);
    }

    private function optionsFor(DialogNode $node): Collection
    {
        if ($node->relationLoaded('options')) {
            return $node->getRelation('options');
        }

        return $node->options()->get();
    }
}

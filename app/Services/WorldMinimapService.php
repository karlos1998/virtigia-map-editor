<?php

namespace App\Services;

use App\Facades\AssetUrl;
use App\Models\Door;
use App\Models\Map;
use App\Models\WorldMinimapNode;
use Illuminate\Support\Facades\DB;

class WorldMinimapService
{
    public function getData(): array
    {
        $nodes = WorldMinimapNode::query()
            ->with('map')
            ->get()
            ->map(function (WorldMinimapNode $node) {
                return [
                    'id' => $node->id,
                    'map_id' => $node->map_id,
                    'x' => $node->x,
                    'y' => $node->y,
                    'map' => [
                        'id' => $node->map?->id,
                        'name' => $node->map?->name,
                        'x' => $node->map?->x,
                        'y' => $node->map?->y,
                        'pvp' => $node->map?->pvp,
                        'thumbnail_src' => $node->map
                            ? ($node->map->thumbnail_src
                                ? AssetUrl::map($node->map->thumbnail_src)
                                : AssetUrl::map($node->map->src))
                            : null,
                    ],
                ];
            })
            ->values();

        return [
            'nodes' => $nodes,
        ];
    }

    public function regenerate(): void
    {
        DB::transaction(function () {
            WorldMinimapNode::query()->delete();

            $mapsById = Map::query()->get()->keyBy('id');
            if ($mapsById->isEmpty()) {
                return;
            }

            $originMapId = $mapsById->has(1) ? 1 : (int)$mapsById->keys()->first();
            $positions = $this->buildPositionsFromEdgeDoors($originMapId);

            foreach ($positions as $mapId => $position) {
                if (!$mapsById->has($mapId)) {
                    continue;
                }

                WorldMinimapNode::query()->create([
                    'map_id' => (int)$mapId,
                    'x' => (int)$position['x'],
                    'y' => (int)$position['y'],
                ]);
            }

        });
    }

    public function updateNodePosition(WorldMinimapNode $node, int $x, int $y): void
    {
        $node->update([
            'x' => $x,
            'y' => $y,
        ]);
    }

    public function addNode(int $mapId, ?int $nearMapId = null): WorldMinimapNode
    {
        $existing = WorldMinimapNode::query()->where('map_id', $mapId)->first();
        if ($existing) {
            return $existing;
        }

        $x = 0;
        $y = 0;

        if ($nearMapId) {
            $anchor = WorldMinimapNode::query()->where('map_id', $nearMapId)->first();
            if ($anchor) {
                $anchorMap = Map::query()->find($nearMapId);
                $x = $anchor->x + (int)($anchorMap?->x ?? 20);
                $y = $anchor->y;
            }
        }

        return WorldMinimapNode::query()->create([
            'map_id' => $mapId,
            'x' => $x,
            'y' => $y,
        ]);
    }

    public function deleteNode(WorldMinimapNode $node): void
    {
        $node->delete();
    }

    private function buildPositionsFromEdgeDoors(int $startMapId): array
    {
        $mapsById = Map::query()->get()->keyBy('id');
        $positions = [];
        $queue = [$startMapId];
        $positions[$startMapId] = ['x' => 0, 'y' => 0];

        while (!empty($queue)) {
            $currentMapId = array_shift($queue);
            $currentPosition = $positions[$currentMapId];

            /** @var Map|null $map */
            $map = $mapsById->get($currentMapId);
            if (!$map) {
                continue;
            }

            $edgeDoors = Door::query()
                ->where('map_id', $map->id)
                ->where(function ($q) use ($map) {
                    $q->where('x', 0)
                        ->orWhere('x', $map->x - 1)
                        ->orWhere('y', 0)
                        ->orWhere('y', $map->y - 1);
                })
                ->get(['map_id', 'x', 'y', 'go_map_id', 'go_x', 'go_y']);

            foreach ($edgeDoors as $door) {
                $targetMapId = (int)$door->go_map_id;
                if (!$targetMapId || isset($positions[$targetMapId])) {
                    continue;
                }

                /** @var Map|null $targetMap */
                $targetMap = $mapsById->get($targetMapId);
                if (!$targetMap) {
                    continue;
                }

                $dx = (int)$door->x - (int)$door->go_x;
                $dy = (int)$door->y - (int)$door->go_y;
                $nextX = $currentPosition['x'] + $dx;
                $nextY = $currentPosition['y'] + $dy;

                // Keep maps visually glued edge-to-edge when doors are on borders.
                if ((int)$door->x === 0) {
                    $nextX = $currentPosition['x'] - (int)$targetMap->x;
                } elseif ((int)$door->x === ((int)$map->x - 1)) {
                    $nextX = $currentPosition['x'] + (int)$map->x;
                }

                if ((int)$door->y === 0) {
                    $nextY = $currentPosition['y'] - (int)$targetMap->y;
                } elseif ((int)$door->y === ((int)$map->y - 1)) {
                    $nextY = $currentPosition['y'] + (int)$map->y;
                }

                $positions[$targetMapId] = [
                    'x' => $nextX,
                    'y' => $nextY,
                ];

                $queue[] = $targetMapId;
            }
        }

        if (empty($positions)) {
            return [];
        }

        $minX = min(array_column($positions, 'x'));
        $minY = min(array_column($positions, 'y'));
        foreach ($positions as $mapId => $position) {
            $positions[$mapId] = [
                'x' => $position['x'] - $minX,
                'y' => $position['y'] - $minY,
            ];
        }

        return $positions;
    }
}

<?php

namespace App\Services\Mcp;

use App\Models\BaseItem;
use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\DialogNode;
use App\Models\Map as GameMap;
use App\Models\Npc;
use App\Models\Quest;

class GameContentSearchService
{
    public function __construct(
        private readonly McpWorldService $worldService,
    ) {}

    /**
     * @param  array<int, string>  $types
     * @return array<string, mixed>
     */
    public function search(string $world, string $query, array $types, ?string $mapName, int $limit): array
    {
        $world = $this->worldService->use($world);
        $types = $types === [] ? ['maps', 'npcs', 'base_npcs', 'items', 'quests', 'dialogs'] : $types;
        $limit = min(max($limit, 1), 25);
        $like = '%'.trim($query).'%';
        $results = [];

        if (in_array('maps', $types, true)) {
            $results['maps'] = GameMap::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'src', 'x', 'y'])
                ->toArray();
        }

        if (in_array('base_npcs', $types, true)) {
            $results['base_npcs'] = BaseNpc::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'src', 'lvl', 'category', 'rank'])
                ->toArray();
        }

        if (in_array('items', $types, true)) {
            $results['items'] = BaseItem::query()
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name', 'src', 'category'])
                ->toArray();
        }

        if (in_array('npcs', $types, true)) {
            $results['npcs'] = Npc::query()
                ->with([
                    'base:id,name,src',
                    'dialog:id,name',
                    'locations.map:id,name',
                ])
                ->whereHas('base', fn ($npcQuery) => $npcQuery->where('name', 'like', $like))
                ->when($mapName, fn ($npcQuery, string $name) => $npcQuery->whereHas(
                    'locations.map',
                    fn ($mapQuery) => $mapQuery->where('name', 'like', '%'.$name.'%'),
                ))
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->map(fn (Npc $npc): array => [
                    'id' => $npc->id,
                    'name' => $npc->base?->name,
                    'base_npc_id' => $npc->base_npc_id,
                    'base_npc_src' => $npc->base?->src,
                    'dialog' => $npc->dialog ? ['id' => $npc->dialog->id, 'name' => $npc->dialog->name] : null,
                    'locations' => $npc->locations->map(fn ($location): array => [
                        'id' => $location->id,
                        'map_id' => $location->map_id,
                        'map_name' => $location->map?->name,
                        'x' => $location->x,
                        'y' => $location->y,
                    ])->all(),
                ])
                ->all();
        }

        if (in_array('quests', $types, true)) {
            $results['quests'] = Quest::query()
                ->with('steps:id,quest_id,name,description,visible_in_quest_list')
                ->where('name', 'like', $like)
                ->orderBy('name')
                ->limit($limit)
                ->get(['id', 'name'])
                ->toArray();
        }

        if (in_array('dialogs', $types, true)) {
            $results['dialogs'] = Dialog::query()
                ->with(['npcs.base:id,name', 'npcs.locations.map:id,name'])
                ->withCount(['nodes', 'edges'])
                ->where(function ($dialogQuery) use ($like): void {
                    $dialogQuery->where('name', 'like', $like)
                        ->orWhereHas('npcs.base', fn ($baseNpcQuery) => $baseNpcQuery->where('name', 'like', $like));
                })
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->map(fn (Dialog $dialog): array => [
                    'id' => $dialog->id,
                    'name' => $dialog->name,
                    'nodes_count' => $dialog->nodes_count,
                    'edges_count' => $dialog->edges_count,
                    'npcs' => $dialog->npcs->map(fn (Npc $npc): array => [
                        'id' => $npc->id,
                        'name' => $npc->base?->name,
                        'maps' => $npc->locations->pluck('map.name')->filter()->unique()->values()->all(),
                    ])->all(),
                ])
                ->all();
        }

        return [
            'world' => $world,
            'query' => $query,
            'results' => $results,
        ];
    }

    /** @return array<string, mixed> */
    public function writingContext(string $world, ?string $topic, ?int $npcId, int $limit): array
    {
        $world = $this->worldService->use($world);
        $limit = min(max($limit, 1), 12);

        $nodes = DialogNode::query()
            ->with(['dialog:id,name', 'options:id,node_id,label,rules,additional_actions,order'])
            ->whereNotNull('content')
            ->where('content', '!=', '')
            ->when($npcId, fn ($nodeQuery, int $id) => $nodeQuery->whereHas(
                'dialog.npcs',
                fn ($npcQuery) => $npcQuery->whereKey($id),
            ))
            ->when($topic, function ($nodeQuery, string $value): void {
                $like = '%'.$value.'%';
                $nodeQuery->where(function ($topicQuery) use ($like): void {
                    $topicQuery->where('content', 'like', $like)
                        ->orWhereHas('dialog', fn ($dialogQuery) => $dialogQuery->where('name', 'like', $like))
                        ->orWhereHas('options', fn ($optionQuery) => $optionQuery->where('label', 'like', $like));
                });
            })
            ->latest('id')
            ->limit($limit)
            ->get();

        if ($nodes->isEmpty() && $topic !== null) {
            $nodes = DialogNode::query()
                ->with(['dialog:id,name', 'options:id,node_id,label,rules,additional_actions,order'])
                ->whereNotNull('content')
                ->where('content', '!=', '')
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        }

        return [
            'world' => $world,
            'guidance' => [
                'Treat examples as tone references, not text to copy.',
                'Prefer short, natural Polish dialogue with the same light dialect level as nearby NPCs.',
                'Do not copy obvious test, placeholder, or malformed content.',
            ],
            'examples' => $nodes->map(fn (DialogNode $node): array => [
                'dialog_id' => $node->source_dialog_id,
                'dialog_name' => $node->dialog?->name,
                'node_type' => $node->type,
                'content' => $node->content,
                'options' => $node->options->map(fn ($option): array => [
                    'label' => $option->label,
                    'rules' => $option->rules,
                    'additional_actions' => $option->additional_actions,
                ])->all(),
            ])->all(),
        ];
    }
}

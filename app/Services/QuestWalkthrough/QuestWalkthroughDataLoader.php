<?php

namespace App\Services\QuestWalkthrough;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Loads a world template into a {@see QuestWalkthroughWorldData} snapshot.
 *
 * Raw query builder access is used on purpose: Eloquent casts reject legacy enum values
 * (e.g. ADD_LEVELS) that still exist in production dialog data.
 */
class QuestWalkthroughDataLoader
{
    /**
     * Tables whose changes may alter a generated walkthrough.
     */
    public const SOURCE_TABLES = [
        'quests',
        'quest_steps',
        'quest_step_auto_progresses',
        'quest_step_auto_progress_mobs',
        'dialogs',
        'dialog_nodes',
        'dialog_node_options',
        'dialog_edges',
        'dialog_counters',
        'npcs',
        'npc_locations',
        'maps',
        'base_npcs',
        'base_npc_mob_species',
        'mob_species',
        'base_items',
    ];

    public function load(string $connection): QuestWalkthroughWorldData
    {
        $db = DB::connection($connection);

        $quests = [];
        foreach ($db->table('quests')->orderBy('id')->get(['id', 'name']) as $row) {
            $quests[(int) $row->id] = ['id' => (int) $row->id, 'name' => (string) $row->name];
        }

        $autoProgress = [];
        foreach ($db->table('quest_step_auto_progresses')->get(['id', 'quest_step_id', 'type', 'time_seconds']) as $row) {
            $autoProgress[(int) $row->quest_step_id] = [
                'id' => (int) $row->id,
                'type' => (string) $row->type,
                'time_seconds' => $row->time_seconds === null ? null : (int) $row->time_seconds,
                'mobs' => [],
            ];
        }
        $autoProgressStepById = collect($autoProgress)->mapWithKeys(fn (array $progress, int $stepId) => [$progress['id'] => $stepId]);
        foreach ($db->table('quest_step_auto_progress_mobs')->orderBy('id')->get() as $row) {
            $stepId = $autoProgressStepById->get((int) $row->quest_step_auto_progress_id);
            if ($stepId === null) {
                continue;
            }
            $autoProgress[$stepId]['mobs'][] = [
                'base_npc_id' => $row->base_npc_id === null ? null : (int) $row->base_npc_id,
                'mob_species_id' => $row->mob_species_id === null ? null : (int) $row->mob_species_id,
                'quantity' => (int) $row->quantity,
            ];
        }

        $stepsByQuest = [];
        foreach ($db->table('quest_steps')->orderBy('id')->get() as $row) {
            $stepsByQuest[(int) $row->quest_id][] = [
                'id' => (int) $row->id,
                'quest_id' => (int) $row->quest_id,
                'name' => (string) $row->name,
                'description' => $row->description,
                'visible_in_quest_list' => (bool) $row->visible_in_quest_list,
                'auto_advance_next_day' => (bool) $row->auto_advance_next_day,
                'auto_advance_to_step_id' => $row->auto_advance_to_step_id === null ? null : (int) $row->auto_advance_to_step_id,
                'auto_progress' => $autoProgress[(int) $row->id] ?? null,
            ];
        }

        $dialogs = [];
        foreach ($db->table('dialogs')->get(['id', 'name']) as $row) {
            $dialogs[(int) $row->id] = ['id' => (int) $row->id, 'name' => $row->name];
        }

        $nodes = [];
        foreach ($db->table('dialog_nodes')->orderBy('id')->get(['id', 'type', 'content', 'source_dialog_id', 'action_data', 'additional_actions', 'shop_id', 'hotel_id']) as $row) {
            $nodes[(int) $row->id] = [
                'id' => (int) $row->id,
                'dialog_id' => (int) $row->source_dialog_id,
                'type' => (string) $row->type,
                'content' => $row->content,
                'action_data' => $this->json($row->action_data),
                'additional_actions' => $this->json($row->additional_actions),
                'shop_id' => $row->shop_id === null ? null : (int) $row->shop_id,
                'hotel_id' => $row->hotel_id === null ? null : (int) $row->hotel_id,
            ];
        }

        $optionsByNode = [];
        foreach ($db->table('dialog_node_options')->orderBy('order')->orderBy('id')->get() as $row) {
            $optionsByNode[(int) $row->node_id][] = [
                'id' => (int) $row->id,
                'node_id' => (int) $row->node_id,
                'label' => $row->label,
                'order' => $row->order === null ? null : (int) $row->order,
                'rules' => $this->json($row->rules) ?? [],
                'additional_action' => $row->additional_action ?: null,
                'additional_actions' => $this->json($row->additional_actions),
            ];
        }

        $edgesByOption = [];
        $edgesByNode = [];
        foreach ($db->table('dialog_edges')->orderBy('id')->get() as $row) {
            $edge = [
                'id' => (int) $row->id,
                'source_node_id' => $row->source_node_id === null ? null : (int) $row->source_node_id,
                'source_option_id' => $row->source_option_id === null ? null : (int) $row->source_option_id,
                'source_handle' => $row->source_handle,
                'target_node_id' => $row->target_node_id === null ? null : (int) $row->target_node_id,
                'rules' => $this->json($row->rules) ?? [],
            ];

            if ($edge['source_option_id'] !== null) {
                $edgesByOption[$edge['source_option_id']][] = $edge;
            } elseif ($edge['source_node_id'] !== null) {
                $edgesByNode[$edge['source_node_id']][] = $edge;
            }
        }

        $locationsByNpc = [];
        foreach ($db->table('npc_locations')->orderBy('id')->get(['npc_id', 'map_id', 'x', 'y']) as $row) {
            $locationsByNpc[(int) $row->npc_id][] = ['map_id' => (int) $row->map_id, 'x' => (int) $row->x, 'y' => (int) $row->y];
        }

        $npcsByDialog = [];
        $npcsByBaseNpc = [];
        foreach ($db->table('npcs')->orderBy('id')->get(['id', 'dialog_id', 'base_npc_id', 'auto_start_dialog', 'enabled']) as $row) {
            if (! (bool) ($row->enabled ?? true)) {
                continue;
            }

            $npc = [
                'id' => (int) $row->id,
                'dialog_id' => $row->dialog_id === null ? null : (int) $row->dialog_id,
                'base_npc_id' => $row->base_npc_id === null ? null : (int) $row->base_npc_id,
                'auto_start_dialog' => (bool) $row->auto_start_dialog,
                'locations' => $locationsByNpc[(int) $row->id] ?? [],
            ];

            if ($npc['dialog_id'] !== null) {
                $npcsByDialog[$npc['dialog_id']][] = $npc;
            }
            if ($npc['base_npc_id'] !== null) {
                $npcsByBaseNpc[$npc['base_npc_id']][] = $npc;
            }
        }

        $baseNpcs = [];
        foreach ($db->table('base_npcs')->get(['id', 'name', 'lvl', 'src', 'rank']) as $row) {
            $baseNpcs[(int) $row->id] = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'lvl' => $row->lvl === null ? null : (int) $row->lvl,
                'src' => $row->src,
                'rank' => $row->rank,
            ];
        }

        $baseNpcIdsBySpecies = [];
        if (Schema::connection($connection)->hasTable('base_npc_mob_species')) {
            foreach ($db->table('base_npc_mob_species')->get(['base_npc_id', 'mob_species_id']) as $row) {
                $baseNpcIdsBySpecies[(int) $row->mob_species_id][] = (int) $row->base_npc_id;
            }
        }

        return new QuestWalkthroughWorldData(
            quests: $quests,
            stepsByQuest: $stepsByQuest,
            dialogs: $dialogs,
            nodes: $nodes,
            optionsByNode: $optionsByNode,
            edgesByOption: $edgesByOption,
            edgesByNode: $edgesByNode,
            npcsByDialog: $npcsByDialog,
            npcsByBaseNpc: $npcsByBaseNpc,
            baseNpcs: $baseNpcs,
            baseNpcIdsBySpecies: $baseNpcIdsBySpecies,
            mobSpecies: $this->names($db, 'mob_species'),
            maps: $this->names($db, 'maps'),
            items: $this->items($db),
            itemSources: $this->itemSources($connection, $db),
            dialogCounters: $this->names($db, 'dialog_counters'),
            seasonalEvents: Schema::connection($connection)->hasTable('seasonal_events') ? $this->names($db, 'seasonal_events') : [],
        );
    }

    /**
     * A cheap signature of every source table; it changes whenever a row is added, removed or updated.
     */
    public function sourceSignature(string $connection): string
    {
        $db = DB::connection($connection);
        $parts = [];

        foreach (self::SOURCE_TABLES as $table) {
            if (! Schema::connection($connection)->hasTable($table)) {
                $parts[$table] = null;

                continue;
            }

            $hasTimestamps = Schema::connection($connection)->hasColumn($table, 'updated_at');
            $row = $db->table($table)
                ->selectRaw($hasTimestamps ? 'COUNT(*) as aggregate_count, MAX(updated_at) as aggregate_updated_at' : 'COUNT(*) as aggregate_count')
                ->first();

            $parts[$table] = [(int) $row->aggregate_count, $hasTimestamps ? (string) $row->aggregate_updated_at : null];
        }

        return hash('sha256', json_encode($parts));
    }

    /**
     * @return array<int, string>
     */
    private function names(ConnectionInterface $db, string $table): array
    {
        $names = [];
        foreach ($db->table($table)->get(['id', 'name']) as $row) {
            $names[(int) $row->id] = (string) $row->name;
        }

        return $names;
    }

    /**
     * @return array<int, array{id: int, name: string, src: ?string}>
     */
    private function items(ConnectionInterface $db): array
    {
        $items = [];
        foreach ($db->table('base_items')->get(['id', 'name', 'src']) as $row) {
            $items[(int) $row->id] = ['id' => (int) $row->id, 'name' => (string) $row->name, 'src' => $row->src];
        }

        return $items;
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function itemSources(string $connection, ConnectionInterface $db): array
    {
        if (! Schema::connection($connection)->hasTable('base_item_usage_views')) {
            return [];
        }

        $sources = [];
        foreach ($db->table('base_item_usage_views')->where('source_count', '>', 0)->get(['base_item_id', 'sources']) as $row) {
            $decoded = $this->json($row->sources);
            if (is_array($decoded) && $decoded !== []) {
                $sources[(int) $row->base_item_id] = $decoded;
            }
        }

        return $sources;
    }

    private function json(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}

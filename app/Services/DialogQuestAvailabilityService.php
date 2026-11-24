<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Collection;

class DialogQuestAvailabilityService
{
    public const TABLE = 'dialog_quest_availability';

    public function __construct(private readonly DialogQuestAvailabilityCompiler $compiler) {}

    public function refresh(Connection $connection): int
    {
        if (! $connection->getSchemaBuilder()->hasTable(self::TABLE)) {
            return 0;
        }

        return $connection->transaction(function () use ($connection): int {
            $changed = 0;
            $connection->table('dialogs')->select('id')->chunkById(100, function (Collection $dialogs) use ($connection, &$changed): void {
                $changed += $this->refreshBatch($connection, $dialogs->pluck('id'));
            });

            $connection->table(self::TABLE)
                ->whereNotExists(fn ($query) => $query->select('id')->from('dialogs')->whereColumn('dialogs.id', self::TABLE.'.dialog_id'))
                ->delete();

            return $changed;
        });
    }

    /** @return array{status: string, reason: ?string, quests: array} */
    public function inspect(Connection $connection, int $dialogId): array
    {
        $row = $connection->getSchemaBuilder()->hasTable(self::TABLE)
            ? $connection->table(self::TABLE)->where('dialog_id', $dialogId)
                ->whereIn('format_version', [2, DialogQuestAvailabilityCompiler::FORMAT_VERSION])
                ->first(['requirements', 'fallback_reason'])
            : null;

        if ($row === null || $row->requirements === null) {
            return [
                'status' => $row === null ? 'pending' : 'uncompiled',
                'reason' => $row?->fallback_reason,
                'quests' => [],
            ];
        }

        $pathsByQuest = collect(json_decode($row->requirements, true, flags: JSON_THROW_ON_ERROR))->groupBy('questId');
        $names = $connection->table('quests')->whereIn('id', $pathsByQuest->keys())->pluck('name', 'id');

        return [
            'status' => 'ready',
            'reason' => null,
            'quests' => $pathsByQuest->map(static fn (Collection $paths, int $questId): array => [
                'id' => $questId,
                'name' => $names->get($questId),
                'paths' => $paths->pluck('conditions')->all(),
            ])->values()->all(),
        ];
    }

    private function refreshBatch(Connection $connection, Collection $dialogIds): int
    {
        $nodes = $connection->table('dialog_nodes')->whereIn('source_dialog_id', $dialogIds)->orderBy('id')
            ->get(['id', 'source_dialog_id', 'type', 'additional_actions']);
        $nodesByDialog = $nodes->groupBy('source_dialog_id');

        $options = $connection->table('dialog_node_options')->whereIn('node_id', $nodes->pluck('id'))->orderBy('id')
            ->get(['id', 'node_id', 'label', 'rules', 'additional_action', 'additional_actions']);
        $optionsByNode = $options->groupBy('node_id');

        $startEdges = $connection->table('dialog_edges as edge')
            ->join('dialog_nodes as source', 'source.id', '=', 'edge.source_node_id')
            ->whereIn('edge.source_dialog_id', $dialogIds)
            ->whereNull('edge.source_option_id')->where('source.type', 'start')->orderBy('edge.id')
            ->get(['edge.id', 'edge.source_dialog_id', 'edge.target_node_id', 'edge.rules'])->groupBy('source_dialog_id');

        $edgesByOption = $connection->table('dialog_edges')->whereIn('source_option_id', $options->pluck('id'))->orderBy('id')
            ->get(['id', 'source_option_id', 'target_node_id', 'rules'])->groupBy('source_option_id');

        $specialNodeIds = $nodes->whereIn('type', ['randomizer', 'minigame'])->pluck('id');
        $edgesByNode = $connection->table('dialog_edges')->whereIn('source_node_id', $specialNodeIds)->orderBy('id')
            ->get(['id', 'source_node_id', 'target_node_id', 'rules'])->groupBy('source_node_id');

        $stepIds = $nodes->concat($options)->flatMap(fn ($entry) => $this->compiler->stepIds($entry->additional_actions))->unique();
        $visibleQuests = $connection->table('quest_steps')->whereIn('id', $stepIds)->where('visible_in_quest_list', true)
            ->pluck('quest_id', 'id')->map(static fn ($id): int => (int) $id);

        $hashes = $connection->table(self::TABLE)->whereIn('dialog_id', $dialogIds)->pluck('source_hash', 'dialog_id');

        $changed = [];
        foreach ($dialogIds as $dialogId) {
            $dialogNodes = $nodesByDialog->get($dialogId, collect())->keyBy('id')->all();
            $dialogOptions = $optionsByNode->only(array_keys($dialogNodes))->map->all();
            $dialogEdges = $edgesByOption->only($dialogOptions->flatten(1)->pluck('id')->all())->map->all();
            $dialogDirectEdges = $edgesByNode->only(array_keys($dialogNodes))->map->all();
            $dialogStarts = $startEdges->get($dialogId, collect())->all();

            $dialogSteps = collect($dialogNodes)->concat($dialogOptions->flatten(1))
                ->flatMap(fn ($entry) => $this->compiler->stepIds($entry->additional_actions));
            $dialogQuests = $visibleQuests->only($dialogSteps->all())->sortKeys()->all();
            $hash = hash('sha256', json_encode([
                DialogQuestAvailabilityCompiler::FORMAT_VERSION,
                $dialogNodes, $dialogOptions, $dialogStarts, $dialogEdges, $dialogQuests,
                $dialogDirectEdges,
            ], JSON_THROW_ON_ERROR));
            if ($hashes->get($dialogId) === $hash) {
                continue;
            }

            $compiled = $this->compiler->compile($dialogNodes, $dialogOptions->all(), $dialogStarts, $dialogEdges->all(), $dialogDirectEdges->all(), $dialogQuests);
            $changed[] = [
                'dialog_id' => $dialogId,
                'source_hash' => $hash,
                'format_version' => DialogQuestAvailabilityCompiler::FORMAT_VERSION,
                'requirements' => $compiled['requirements'] === null ? null : json_encode($compiled['requirements'], JSON_THROW_ON_ERROR),
                'fallback_reason' => $compiled['fallback_reason'],
            ];
        }

        if ($changed !== []) {
            $connection->table(self::TABLE)->upsert($changed, ['dialog_id'], ['source_hash', 'format_version', 'requirements', 'fallback_reason']);
        }

        return count($changed);
    }
}

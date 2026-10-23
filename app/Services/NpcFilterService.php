<?php

namespace App\Services;

use App\Models\BaseNpc;
use App\Models\Dialog;
use App\Models\Map;
use Illuminate\Database\Eloquent\Builder;

class NpcFilterService
{
    /** @param array<string, mixed> $filters */
    public function apply(Builder $query, array $filters, bool $baseList = false): Builder
    {
        $baseFilters = array_intersect_key($filters, array_flip(['category', 'rank', 'is_aggressive', 'min_lvl', 'max_lvl']));
        $instanceFilters = array_diff_key($filters, $baseFilters);

        if ($baseList) {
            $this->applyBaseFilters($query, $baseFilters);
            if (array_filter($instanceFilters, fn ($value) => $value !== null && $value !== '') !== []) {
                $query->whereHas('locations', fn (Builder $instances) => $this->applyInstanceFilters($instances, $instanceFilters));
            }
        } else {
            if ($baseFilters !== []) {
                $query->whereHas('base', fn (Builder $base) => $this->applyBaseFilters($base, $baseFilters));
            }
            $this->applyInstanceFilters($query, $instanceFilters);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function applyBaseFilters(Builder $query, array $filters): void
    {
        foreach (['category', 'rank', 'is_aggressive'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (isset($filters['min_lvl'])) {
            $query->where('lvl', '>=', $filters['min_lvl']);
        }
        if (isset($filters['max_lvl'])) {
            $query->where('lvl', '<=', $filters['max_lvl']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyInstanceFilters(Builder $query, array $filters): void
    {
        foreach (['base_npc_id', 'dialog_id', 'enabled', 'auto_start_dialog'] as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        foreach (['has_dialog' => 'dialog_id', 'in_group' => 'group_id'] as $filter => $column) {
            if (isset($filters[$filter])) {
                $filters[$filter] ? $query->whereNotNull($column) : $query->whereNull($column);
            }
        }
        if (isset($filters['map_id'])) {
            $query->whereHas('locations', fn (Builder $locations) => $locations->where('map_id', $filters['map_id']));
        }
        if (isset($filters['placement'])) {
            match ($filters['placement']) {
                'none' => $query->doesntHave('locations'),
                'one' => $query->has('locations', '=', 1),
                'many' => $query->has('locations', '>', 1),
            };
        }
    }

    /** @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function options(array $filters): array
    {
        foreach (['map_id', 'base_npc_id', 'dialog_id', 'has_dialog', 'enabled', 'in_group', 'auto_start_dialog', 'is_aggressive', 'min_lvl', 'max_lvl'] as $field) {
            if (isset($filters[$field])) {
                $filters[$field] = (int) $filters[$field];
            }
        }

        return [
            'filters' => $filters,
            'selectedMap' => isset($filters['map_id']) ? Map::query()->find($filters['map_id'], ['id', 'name']) : null,
            'selectedDialog' => isset($filters['dialog_id']) ? Dialog::query()->find($filters['dialog_id'], ['id', 'name']) : null,
            'selectedBaseNpc' => isset($filters['base_npc_id']) ? BaseNpc::query()->find($filters['base_npc_id'], ['id', 'name']) : null,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Map;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MapCityService
{
    /** @return Collection<int, Map> */
    public function relatedMaps(Map $map): Collection
    {
        $visited = [$map->id => true];
        $pending = new ModelCollection([$map]);
        $related = collect();

        while ($pending->isNotEmpty()) {
            $pending->load('doors.targetMap');
            $next = new ModelCollection;
            foreach ($pending as $source) {
                foreach ($source->doors as $door) {
                    if ($door->x <= 0 || $door->y <= 0 || $door->x >= $source->x - 1 || $door->y >= $source->y - 1) {
                        continue;
                    }
                    $target = $door->targetMap;
                    if ($target === null || isset($visited[$target->id])) {
                        continue;
                    }
                    $visited[$target->id] = true;
                    $related->push($target);
                    $next->push($target);
                }
            }
            $pending = $next;
        }

        return $related->sortBy('name')->values();
    }

    /** @param array<int, int> $relatedMapIds */
    public function update(Map $map, bool $isCity, array $relatedMapIds): void
    {
        $allowedIds = $isCity ? $this->relatedMaps($map)->pluck('id')->all() : [];
        if (array_diff($relatedMapIds, $allowedIds) !== []) {
            throw ValidationException::withMessages(['related_map_ids' => 'Wybierz wyłącznie powiązane lokalizacje z listy.']);
        }
        $this->updateMany([$map->id, ...$relatedMapIds], $isCity);
    }

    /** @param array<int, int> $mapIds */
    public function updateMany(array $mapIds, bool $isCity): void
    {
        $model = new Map;
        $model->getConnection()->transaction(function () use ($model, $mapIds, $isCity): void {
            foreach ($model->newQuery()->whereIn('id', $mapIds)->get() as $map) {
                $map->update(['is_city' => $isCity]);
            }
        });
    }
}

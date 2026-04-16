<?php

namespace App\Jobs;

use App\Models\BaseItem;
use App\Models\DynamicModel;
use App\Models\Map;
use App\Services\BaseItemTeleportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FillMissingTeleportMapNamesForWorldJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        private readonly string $world,
    ) {}

    public function handle(BaseItemTeleportService $baseItemTeleportService): void
    {
        DynamicModel::setGlobalConnection($this->world);

        $baseItems = BaseItem::on($this->world)
            ->select(['id', 'attributes'])
            ->whereNotNull('attributes')
            ->whereRaw('JSON_TYPE(JSON_EXTRACT(attributes, "$.teleportTo")) = ?', ['ARRAY'])
            ->where(function ($query): void {
                $query
                    ->whereRaw('JSON_LENGTH(JSON_EXTRACT(attributes, "$.teleportTo")) < 4')
                    ->orWhereRaw('JSON_EXTRACT(attributes, "$.teleportTo[3]") IS NULL')
                    ->orWhereRaw('LOWER(TRIM(JSON_UNQUOTE(JSON_EXTRACT(attributes, "$.teleportTo[3]")))) IN (?, ?, ?)', ['', 'undefined', 'null']);
            })
            ->get();

        if ($baseItems->isEmpty()) {
            Log::info("No base items with missing teleport map names found for [$this->world].");

            return;
        }

        $mapNamesById = Map::on($this->world)
            ->whereIn('id', $baseItems
                ->pluck('attributes')
                ->map(fn ($attributes) => $attributes['teleportTo'][0] ?? null)
                ->filter(fn ($mapId) => is_numeric($mapId))
                ->map(fn ($mapId) => (int) $mapId)
                ->unique()
                ->values()
                ->all()
            )
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id) => [(int) $id => $name])
            ->all();

        $updatedCount = 0;

        foreach ($baseItems as $baseItem) {
            $attributes = is_array($baseItem->attributes) ? $baseItem->attributes : [];
            $updatedAttributes = $baseItemTeleportService->fillMissingTeleportMapName($attributes, $mapNamesById);

            if ($updatedAttributes === $attributes) {
                continue;
            }

            $baseItem->forceFill([
                'attributes' => $updatedAttributes,
            ])->saveQuietly();

            $updatedCount++;
        }

        Log::info("Filled missing teleport map names for [$this->world].", [
            'matched_items' => $baseItems->count(),
            'updated_items' => $updatedCount,
        ]);
    }
}

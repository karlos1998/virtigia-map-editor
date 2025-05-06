<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseNpcResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $lootCounts = [
            'total' => 0,
            'common' => 0,
            'unique' => 0,
            'heroic' => 0,
            'legendary' => 0,
        ];

        if ($this->resource->relationLoaded('loots')) {
            $lootCounts['total'] = $this->resource->loots->count();

            // Count loots by rarity
            foreach ($this->resource->loots as $loot) {
                if (isset($loot->rarity) && array_key_exists($loot->rarity, $lootCounts)) {
                    $lootCounts[$loot->rarity]++;
                }
            }
        } else {
            // If loots are not loaded, we can still get the total count
            $lootCounts['total'] = $this->resource->loots()->count();
        }

        return [
            ...parent::toArray($request),

            'location_count' => $this->resource->locations()->count(),
            'profession_name' => $this->resource->profession->description(),
            'loot_counts' => $lootCounts,
            'loots' => $this->whenLoaded('loots', fn() => BaseItemResource::collection($this->resource->loots)),
//            'loots' => BaseItemResource::collection($this->resource->loots),

            'src' => config('assets.url') . config('assets.dirs.npcs') . $this->resource->src
        ];
    }
}

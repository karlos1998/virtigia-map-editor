<?php

namespace App\Http\Resources;

use App\Enums\BaseNpcRank;
use App\Models\Npc;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TitanDoorResource extends JsonResource
{
    /**
     * Get the titan NPC for this door
     */
    protected function getTitanNpc()
    {
        return Npc::whereHas('base', function ($query) {
            $query->where('rank', BaseNpcRank::TITAN);
        })
        ->whereHas('locations', function ($query) {
            $query->where('map_id', $this->resource->go_map_id);
        })
        ->with('base')
        ->first();
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $titanNpc = $this->getTitanNpc();

        return [
            ...parent::toArray($request),

            'name' => $this->resource->targetMap->name,
            'source_map' => $this->whenLoaded('map', function() {
                return [
                    'id' => $this->resource->map->id,
                    'name' => $this->resource->map->name,
                ];
            }),
            'target_map' => $this->whenLoaded('targetMap', function() {
                return [
                    'id' => $this->resource->targetMap->id,
                    'name' => $this->resource->targetMap->name,
                ];
            }),
            'titan' => $titanNpc ? [
                'id' => $titanNpc->id,
                'name' => $titanNpc->base->name,
                'level' => $titanNpc->base->lvl,
            ] : null,

            'double_sided' => $this->resource->doubleSided(),

            'required_base_item' => $this->whenLoaded('requiredBaseItem', function() {
                return BaseItemResource::make($this->resource->requiredBaseItem);
            }),
        ];
    }
}

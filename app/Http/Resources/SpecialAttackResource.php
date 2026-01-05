<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecialAttackResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),

            'attack_type' => $this->resource->attack_type->description(),
            'target' => $this->resource->target->description(),

            'effects' => $this->whenLoaded('effects', function () {
                return $this->resource->effects->map(function ($effect) {
                    return [
                        'type' => $effect->type->description(),
                        'value' => $effect->value,
                        'duration' => $effect->duration,
                    ];
                });
            }),

            'damages' => $this->whenLoaded('damages', function () {
                return $this->resource->damages->map(function ($damage) {
                    return [
                        'element' => $damage->element->description(),
                        'min_damage' => $damage->min_damage,
                        'max_damage' => $damage->max_damage,
                    ];
                });
            }),

            'base_npcs_count' => $this->resource->baseNpcs()->count(),

            'baseNpcs' => $this->whenLoaded('baseNpcs', function () {
                return $this->resource->baseNpcs->map(function ($baseNpc) {
                    return [
                        'id' => $baseNpc->id,
                        'name' => $baseNpc->name,
                        'lvl' => $baseNpc->lvl,
                        'profession' => $baseNpc->profession->description(),
                        'rank' => $baseNpc->rank->description(),
                        'category' => $baseNpc->category->description(),
                        'is_aggressive' => $baseNpc->is_aggressive,
                        'location_count' => $baseNpc->locations()->count(),
                    ];
                });
            }),
        ];
    }
}

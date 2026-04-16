<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoorResource extends JsonResource
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

            'name' => $this->resource->targetMap?->name ?? 'Brak mapy docelowej',

            'double_sided' => $this->resource->doubleSided(),

            'map' => $this->whenLoaded('map', function () {
                return MapResource::make($this->resource->map);
            }),

            'target_map' => $this->whenLoaded('targetMap', function () {
                return MapResource::make($this->resource->targetMap);
            }),

            'required_base_item' => $this->whenLoaded('requiredBaseItem', function () {
                return BaseItemResource::make($this->resource->requiredBaseItem);
            }),
        ];
    }
}

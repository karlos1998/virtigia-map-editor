<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RenewableMapItemWithMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'map_id' => $this->map_id,
            'base_item_id' => $this->base_item_id,
            'x' => $this->x,
            'y' => $this->y,
            'respawn_time_seconds' => $this->respawn_time_seconds,
            'item' => new BaseItemResource($this->baseItem),
            'map' => new MapResource($this->map),
        ];
    }
}

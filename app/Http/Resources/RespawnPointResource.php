<?php

namespace App\Http\Resources;

use App\Models\RespawnPoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read RespawnPoint $resource
 */
class RespawnPointResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'map_id' => $this->resource->map_id,
            'map_name' => $this->resource->map->name,
            'x' => $this->resource->x,
            'y' => $this->resource->y,
        ];
    }
}

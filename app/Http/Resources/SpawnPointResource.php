<?php

namespace App\Http\Resources;

use App\Models\SpawnPoint;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read SpawnPoint $resource
 */
class SpawnPointResource extends JsonResource
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
            'map_name' => $this->resource->map ? $this->resource->map->name : null,
            'x' => $this->resource->x,
            'y' => $this->resource->y,
            'profession' => $this->resource->profession->value,
            'profession_name' => $this->resource->profession->description(),
        ];
    }
}

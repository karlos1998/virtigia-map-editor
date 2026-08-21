<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapTrackCheckpointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'sequence' => $this->resource->sequence,
            'map' => MapResource::make($this->whenLoaded('map')),
            'tiles' => $this->whenLoaded('tiles', fn () => $this->resource->tiles
                ->map(fn ($tile): array => ['x' => $tile->x, 'y' => $tile->y])
                ->values()),
        ];
    }
}

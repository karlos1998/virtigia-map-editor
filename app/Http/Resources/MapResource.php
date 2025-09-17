<?php

namespace App\Http\Resources;

use App\Models\Map;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Map $resource
 */
class MapResource extends JsonResource
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
            'src' => config('assets.url') . config('assets.dirs.maps') . $this->resource->src,
            'battleground' => $this->resource->battleground,
            'respawn_point' => RespawnPointResource::make($this->resource->respawnPoint),
        ];
    }
}

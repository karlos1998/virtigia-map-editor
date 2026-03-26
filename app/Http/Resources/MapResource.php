<?php

namespace App\Http\Resources;

use App\Facades\AssetUrl;
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
            'src' => AssetUrl::map($this->resource->src),
            'thumbnail_src' => $this->resource->thumbnail_src ? AssetUrl::map($this->resource->thumbnail_src) : AssetUrl::map($this->resource->src),
            'battleground' => $this->resource->battleground,
            'battleground2' => $this->resource->battleground2,
            'respawn_point' => RespawnPointResource::make($this->resource->respawnPoint),
            'is_teleport_locked' => $this->resource->is_teleport_locked,
        ];
    }
}

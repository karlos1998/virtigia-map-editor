<?php

namespace App\Http\Resources;

use App\Facades\AssetUrl;
use App\Models\NpcLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read NpcLocation $resource
 */
class NpcLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $map = $this->resource->map;

        return [
            'id' => $this->resource->id,
            'map_id' => $this->resource->map_id,
            'map_name' => $map->name,
            'map_src' => AssetUrl::map($map->src),
            'map_width' => $map->x,
            'map_height' => $map->y,
            'x' => $this->resource->x,
            'y' => $this->resource->y,
        ];
    }
}

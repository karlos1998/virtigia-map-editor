<?php

namespace App\Http\Resources;

use App\Models\Npc;
use App\Models\NpcLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Npc $resource
 */
class NpcResource extends JsonResource
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

            'name' => $this->resource->base->name,
            'src' => config('assets.url') . config('assets.dirs.npcs') . $this->resource->base->src,
            'lvl' => $this->resource->base->lvl,
            'type' => $this->resource->base->type,
            'grp' => $this->resource->grp,

            $this->mergeWhen($this->resource->pivot?->x !== null && $this->resource->pivot?->y !== null, fn() => [
                'location' => [
                    'id' => $this->resource->pivot->id,
                    'map_id' => $this->resource->pivot->map_id,
                    'x' => $this->resource->pivot->x,
                    'y' => $this->resource->pivot->y,
                ],
            ]),

            'locations' => $this->whenLoaded('locations', fn() => NpcLocationResource::collection($this->resource->locations)),

            'dialog' => $this->whenLoaded('dialog', fn() => DialogResource::make($this->resource->dialog)),

        ];
    }
}

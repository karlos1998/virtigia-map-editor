<?php

namespace App\Http\Resources;

use App\Models\Npc;
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
            'src' => $this->resource->base->src,
            'lvl' => $this->resource->base->lvl,

            $this->mergeWhen($this->resource->pivot?->x !== null && $this->resource->pivot?->y !== null, fn() => [
                'location' => [
                    'x' => $this->resource->pivot->x,
                    'y' => $this->resource->pivot->y,
                ],
            ])
        ];
    }
}

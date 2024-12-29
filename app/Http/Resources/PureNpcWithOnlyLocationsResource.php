<?php

namespace App\Http\Resources;

use App\Models\Npc;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Npc $resource
 */
class PureNpcWithOnlyLocationsResource extends JsonResource
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

            'locations' => NpcLocationResource::collection($this->resource->locations)
        ];
    }
}

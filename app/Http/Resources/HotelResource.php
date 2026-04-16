<?php

namespace App\Http\Resources;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Hotel $resource
 */
class HotelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'rooms_count' => $this->resource->rooms_count ?? $this->resource->rooms()->count(),
            'rooms' => $this->whenLoaded('rooms', function () {
                return HotelRoomResource::collection($this->resource->rooms);
            }),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}

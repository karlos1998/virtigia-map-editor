<?php

namespace App\Http\Resources;

use App\Models\HotelRoom;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read HotelRoom $resource
 */
class HotelRoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'hotel_id' => $this->resource->hotel_id,
            'base_item_id' => $this->resource->base_item_id,
            'door_id' => $this->resource->door_id,
            'price' => $this->resource->price,
            'base_item' => $this->whenLoaded('baseItem', function () {
                return BaseItemResource::make($this->resource->baseItem);
            }),
            'door' => $this->whenLoaded('door', function () {
                return DoorResource::make($this->resource->door);
            }),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}

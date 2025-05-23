<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoorResource extends JsonResource
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

            'name' => $this->resource->targetMap->name,

            'double_sided' => $this->resource->doubleSided(),

            'required_base_item' => $this->whenLoaded('requiredBaseItem', function() {
                return BaseItemResource::make($this->resource->requiredBaseItem);
            }),
        ];
    }
}

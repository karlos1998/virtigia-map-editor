<?php

namespace App\Http\Resources;

use App\Models\Dialog;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Shop $resource
 */
class ShopResource extends JsonResource
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
            'name' => $this->resource->name,

            'items_count' => $this->resource->items()->count(),

            'dialogs_count' => $this->resource->dialogs()->count(),

            'npcs' => $this->whenLoaded('dialogs', fn() => NpcResource::collection($this->resource->dialogs->flatMap(function (Dialog $dialog) {
                return $dialog->npcs;
            }))),
        ];
    }
}

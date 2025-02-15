<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseNpcResource extends JsonResource
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

            'location_count' => $this->resource->locations()->count(),

            'profession_name' => $this->resource->profession->description(),

            'loots' => $this->whenLoaded('loots', fn() => BaseItemResource::collection($this->resource->loots)),
//            'loots' => BaseItemResource::collection($this->resource->loots),

            'src' => config('assets.url') . config('assets.dirs.npcs') . $this->resource->src
        ];
    }
}

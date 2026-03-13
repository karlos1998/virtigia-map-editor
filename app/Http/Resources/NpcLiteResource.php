<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NpcLiteResource extends JsonResource
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
        ];
    }
}

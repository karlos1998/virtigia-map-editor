<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DialogResource extends JsonResource
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
            'npcs_count' => $this->resource->npcs_count ?? $this->resource->npcs()->count(),
            'last_activity_at' => $this->resource->last_activity_at,
            'last_editor_id' => $this->resource->last_editor_id ? (int) $this->resource->last_editor_id : null,
            'last_editor_name' => $this->resource->last_editor_name,

            'npcs' => $this->whenLoaded('npcs', fn () => NpcResource::collection($this->resource->npcs)),
        ];
    }
}

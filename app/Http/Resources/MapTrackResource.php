<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MapTrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'enabled' => $this->resource->enabled,
            'dialog_counter_id' => $this->resource->dialog_counter_id,
            'dialog_counter' => $this->whenLoaded('dialogCounter', fn (): array => [
                'id' => $this->resource->dialogCounter->id,
                'name' => $this->resource->dialogCounter->name,
                'scope' => $this->resource->dialogCounter->scope,
            ]),
            'checkpoints_count' => $this->whenCounted('checkpoints'),
            'checkpoints' => MapTrackCheckpointResource::collection($this->whenLoaded('checkpoints')),
        ];
    }
}

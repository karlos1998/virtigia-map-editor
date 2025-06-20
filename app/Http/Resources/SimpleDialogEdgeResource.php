<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleDialogEdgeResource extends JsonResource
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
            'rules' => $this->resource->rules,
            'source_dialog_id' => $this->resource->source_dialog_id,
            'source_option_id' => $this->resource->source_option_id,
            'target_node_id' => $this->resource->target_node_id,
        ];
    }
}

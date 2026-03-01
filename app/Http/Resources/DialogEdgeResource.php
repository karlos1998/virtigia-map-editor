<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DialogEdgeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sourceOptionExists = $this->resource->sourceOption()->exists();
        $sourceNodeExists = $this->resource->sourceNode()->exists();

        $sourceId = $sourceOptionExists ?
            $this->resource->sourceOption->node->id :
            ($sourceNodeExists
                ? $this->resource->sourceNode->id
                : $this->resource->sourceDialog->nodes()->where('type', 'start')->limit(1)->first()?->id);

        return [
            //            'id' => "{$sourceId}->{$this->resource->targetNode->id}",
            'id' => "{$this->resource->id}",
            'type' => 'default',
            'source' => "{$sourceId}",
            'target' => "{$this->resource->targetNode->id}",
            $this->mergeWhen($sourceOptionExists, fn () => [
                'sourceHandle' => "source-{$this->resource->sourceOption->id}",
            ]),
            'data' => (object) [
                'dialog_id' => $this->resource->source_dialog_id,
            ],
            'label' => '',

        ];
    }
}

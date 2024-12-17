<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use stdClass;

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
        $sourceId = $sourceOptionExists ? $this->resource->sourceOption->node->id : $this->resource->sourceDialog->id;
        return [
            'id' => "{$sourceId}->{$this->resource->targetNode->id}",
            'type' => 'default',
            'source' => "{$sourceId}",
            'target' => "{$this->resource->targetNode->id}",
            $this->mergeWhen($sourceOptionExists, fn() => [
                'sourceHandle' => "source-{$this->resource->sourceOption->id}",
            ]),
            'data' => new StdClass(),
            'label' => '',

        ];
    }
}

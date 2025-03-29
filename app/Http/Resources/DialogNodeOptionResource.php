<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class DialogNodeOptionResource extends JsonResource
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
            'edges' => $this->resource->edges->map(function($edge) {
                return [
                    'edge_id' => $edge->id,
                    'node' => $this->when($edge->targetNode, function() use ($edge) {
                        return [
                            'id' => $edge->targetNode->id,
                            'type' => $edge->targetNode->type,
                            'content' => $edge->targetNode->content,
                        ];
                    }),
                    'rules' => $edge->rules,
                ];
            }),
        ];
    }
}

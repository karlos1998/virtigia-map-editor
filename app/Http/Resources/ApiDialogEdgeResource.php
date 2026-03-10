<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiDialogEdgeResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'source_option_id', type: 'integer', nullable: true),
        new OA\Property(property: 'source_node_id', type: 'integer', nullable: true),
        new OA\Property(property: 'target_node_id', type: 'integer'),
        new OA\Property(property: 'rules', type: 'object', nullable: true),
    ]
)]
class ApiDialogEdgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_option_id' => $this->source_option_id,
            'source_node_id' => $this->source_node_id,
            'target_node_id' => $this->target_node_id,
            'rules' => $this->rules,
        ];
    }
}

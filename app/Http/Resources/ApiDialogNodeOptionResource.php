<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiDialogNodeOptionResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'label', type: 'string', nullable: true),
        new OA\Property(property: 'rules', type: 'object', nullable: true),
        new OA\Property(property: 'additional_action', type: 'string', nullable: true),
        new OA\Property(property: 'cooldown', type: 'integer', nullable: true),
        new OA\Property(property: 'order', type: 'integer', nullable: true),
        new OA\Property(
            property: 'edges',
            type: 'array',
            items: new OA\Items(ref: ApiDialogEdgeResource::class)
        ),
    ]
)]
class ApiDialogNodeOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'rules' => $this->rules,
            'additional_action' => $this->additional_action?->value ?? $this->additional_action,
            'cooldown' => $this->cooldown,
            'order' => $this->order,
            'edges' => ApiDialogEdgeResource::collection($this->whenLoaded('edges')),
        ];
    }
}

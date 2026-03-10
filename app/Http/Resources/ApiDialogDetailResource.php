<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiDialogDetailResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(
            property: 'npcs',
            type: 'array',
            items: new OA\Items(ref: ApiDialogNpcResource::class)
        ),
        new OA\Property(
            property: 'nodes',
            type: 'array',
            items: new OA\Items(ref: ApiDialogNodeResource::class)
        ),
        new OA\Property(
            property: 'edges',
            type: 'array',
            items: new OA\Items(ref: ApiDialogEdgeResource::class)
        ),
        new OA\Property(
            property: 'related_quests',
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                ]
            )
        ),
    ]
)]
class ApiDialogDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'npcs' => ApiDialogNpcResource::collection($this->whenLoaded('npcs')),
            'nodes' => ApiDialogNodeResource::collection($this->whenLoaded('nodes')),
            'edges' => ApiDialogEdgeResource::collection($this->whenLoaded('edges')),
            'related_quests' => $this->getRelatedQuests()->map(static fn ($quest): array => [
                'id' => $quest->id,
                'name' => $quest->name,
            ])->values(),
        ];
    }
}

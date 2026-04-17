<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiDialogNodeResource::class,
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true),
        new OA\Property(property: 'position', type: 'object', nullable: true),
        new OA\Property(property: 'action_data', type: 'object', nullable: true),
        new OA\Property(property: 'additional_actions', type: 'object', nullable: true),
        new OA\Property(property: 'shop_id', type: 'integer', nullable: true),
        new OA\Property(property: 'shop_name', type: 'string', nullable: true),
        new OA\Property(property: 'hotel_id', type: 'integer', nullable: true),
        new OA\Property(property: 'hotel_name', type: 'string', nullable: true),
        new OA\Property(
            property: 'options',
            type: 'array',
            items: new OA\Items(ref: ApiDialogNodeOptionResource::class)
        ),
    ],
    type: 'object'
)]
class ApiDialogNodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'content' => $this->content,
            'position' => $this->position,
            'action_data' => $this->action_data,
            'additional_actions' => $this->additional_actions,
            'shop_id' => $this->shop_id,
            'shop_name' => $this->shop?->name,
            'hotel_id' => $this->hotel_id,
            'hotel_name' => $this->hotel?->name,
            'options' => ApiDialogNodeOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}

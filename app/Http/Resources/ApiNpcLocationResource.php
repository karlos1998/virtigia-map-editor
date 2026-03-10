<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiNpcLocationResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'map_id', type: 'integer'),
        new OA\Property(property: 'map_name', type: 'string', nullable: true),
        new OA\Property(property: 'x', type: 'integer'),
        new OA\Property(property: 'y', type: 'integer'),
    ]
)]
class ApiNpcLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'map_id' => $this->map_id,
            'map_name' => $this->map?->name,
            'x' => $this->x,
            'y' => $this->y,
        ];
    }
}

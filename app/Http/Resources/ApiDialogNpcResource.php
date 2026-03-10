<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiDialogNpcResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'base_npc_id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'lvl', type: 'integer', nullable: true),
        new OA\Property(property: 'rank', type: 'string', nullable: true),
        new OA\Property(property: 'enabled', type: 'boolean'),
        new OA\Property(property: 'locations_count', type: 'integer'),
    ]
)]
class ApiDialogNpcResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'base_npc_id' => $this->base_npc_id,
            'name' => $this->base?->name,
            'lvl' => $this->base?->lvl,
            'rank' => $this->base?->rank?->value ?? $this->base?->rank,
            'enabled' => (bool) $this->enabled,
            'locations_count' => (int) ($this->locations_count ?? $this->locations?->count() ?? 0),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiNpcListResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'base_npc_id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'src', type: 'string'),
        new OA\Property(property: 'lvl', type: 'integer'),
        new OA\Property(property: 'rank', type: 'string'),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'enabled', type: 'boolean'),
        new OA\Property(property: 'group_id', type: 'integer', nullable: true),
        new OA\Property(property: 'in_group', type: 'boolean'),
        new OA\Property(property: 'location_count', type: 'integer'),
    ]
)]
class ApiNpcListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'base_npc_id' => $this->base_npc_id,
            'name' => $this->base?->name,
            'src' => $this->base?->src
                ? config('assets.url').config('assets.dirs.npcs').$this->base->src
                : null,
            'lvl' => $this->base?->lvl,
            'rank' => $this->base?->rank?->value ?? $this->base?->rank,
            'category' => $this->base?->category?->value ?? $this->base?->category,
            'enabled' => (bool) $this->enabled,
            'group_id' => $this->group_id,
            'in_group' => $this->group_id !== null,
            'location_count' => (int) ($this->locations_count ?? $this->locations?->count() ?? 0),
        ];
    }
}

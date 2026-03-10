<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiBaseNpcListResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'src', type: 'string'),
        new OA\Property(property: 'lvl', type: 'integer'),
        new OA\Property(property: 'rank', type: 'string'),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'type', type: 'integer'),
        new OA\Property(property: 'is_aggressive', type: 'boolean'),
    ]
)]
class ApiBaseNpcListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'src' => config('assets.url').config('assets.dirs.npcs').$this->src,
            'lvl' => $this->lvl,
            'rank' => $this->rank?->value ?? $this->rank,
            'category' => $this->category?->value ?? $this->category,
            'type' => $this->type,
            'is_aggressive' => (bool) $this->is_aggressive,
        ];
    }
}

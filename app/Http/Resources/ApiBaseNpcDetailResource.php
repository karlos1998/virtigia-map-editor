<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiBaseNpcDetailResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'src', type: 'string'),
        new OA\Property(property: 'lvl', type: 'integer'),
        new OA\Property(property: 'rank', type: 'string'),
        new OA\Property(property: 'category', type: 'string'),
        new OA\Property(property: 'profession', type: 'string', nullable: true),
        new OA\Property(property: 'type', type: 'integer'),
        new OA\Property(property: 'is_aggressive', type: 'boolean'),
        new OA\Property(property: 'divine_intervention', type: 'boolean', nullable: true),
        new OA\Property(property: 'guaranteed_loot', type: 'boolean'),
        new OA\Property(property: 'min_respawn_time', type: 'integer', nullable: true),
        new OA\Property(property: 'max_respawn_time', type: 'integer', nullable: true),
    ]
)]
class ApiBaseNpcDetailResource extends JsonResource
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
            'profession' => $this->profession?->value ?? $this->profession,
            'type' => $this->type,
            'is_aggressive' => (bool) $this->is_aggressive,
            'divine_intervention' => $this->divine_intervention,
            'guaranteed_loot' => (bool) $this->guaranteed_loot,
            'min_respawn_time' => $this->min_respawn_time,
            'max_respawn_time' => $this->max_respawn_time,
        ];
    }
}

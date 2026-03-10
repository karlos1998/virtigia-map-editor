<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: MapDetailResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'x', type: 'integer'),
        new OA\Property(property: 'y', type: 'integer'),
        new OA\Property(property: 'src', type: 'string'),
        new OA\Property(property: 'thumbnail_src', type: 'string'),
        new OA\Property(property: 'pvp', type: 'integer'),
        new OA\Property(property: 'battleground', type: 'string', nullable: true),
        new OA\Property(property: 'battleground2', type: 'string', nullable: true),
        new OA\Property(property: 'is_teleport_locked', type: 'boolean'),
        new OA\Property(property: 'respawn_point_id', type: 'integer', nullable: true),
        new OA\Property(property: 'water', type: 'string'),
        new OA\Property(property: 'col', type: 'string'),
    ]
)]
class MapDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'x' => $this->x,
            'y' => $this->y,
            'src' => config('assets.url').config('assets.dirs.maps').$this->src,
            'thumbnail_src' => $this->thumbnail_src
                ? config('assets.url').config('assets.dirs.maps').$this->thumbnail_src
                : config('assets.url').config('assets.dirs.maps').$this->src,
            'pvp' => $this->pvp?->value ?? $this->pvp,
            'battleground' => $this->battleground,
            'battleground2' => $this->battleground2,
            'is_teleport_locked' => (bool) $this->is_teleport_locked,
            'respawn_point_id' => $this->respawn_point_id,
            'water' => $this->water,
            'col' => $this->col,
        ];
    }
}

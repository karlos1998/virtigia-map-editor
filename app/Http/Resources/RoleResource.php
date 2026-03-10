<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RoleResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', nullable: true),
        new OA\Property(property: 'name', type: 'string', nullable: true),
        new OA\Property(property: 'display_name', type: 'string', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ]
)]
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_string($this->resource)) {
            return [
                'name' => $this->resource,
                'display_name' => $this->resource,
            ];
        }

        $role = is_object($this->resource) ? (array) $this->resource : $this->resource;

        if (! is_array($role)) {
            return [];
        }

        return array_filter(
            Arr::only($role, ['id', 'name', 'display_name', 'description']),
            static fn (mixed $value): bool => $value !== null
        );
    }
}

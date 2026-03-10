<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: UserProfileResource::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'login', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(ref: RoleResource::class)
        ),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(ref: PermissionResource::class)
        ),
        new OA\Property(property: 'forum_background_src', type: 'string', nullable: true),
        new OA\Property(property: 'src', type: 'string'),
    ]
)]
class UserProfileResource extends JsonResource
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
            'login' => $this->login,
            'email' => $this->email,
            'roles' => RoleResource::collection(collect($this->roles)->values())->resolve(),
            'permissions' => $this->when(
                $request->user() && $request->user()->id === $this->id,
                fn (): array => PermissionResource::collection(collect($this->permissions)->values())->resolve()
            ),
            'forum_background_src' => $this->forum_background_src,
            'src' => str_replace('imgimg', 'img', config('assets.url').$this->src),
        ];
    }
}

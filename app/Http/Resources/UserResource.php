<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'roles' => $this->roles,
            'permissions' => $this->when($request->user() && $request->user()->id === $this->id, $this->permissions),
            'forum_background_src' => $this->forum_background_src,
            'src' => str_replace('imgimg', 'img', config('assets.url') . $this->src),
        ];
    }
}

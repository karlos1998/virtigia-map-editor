<?php

namespace App\Http\Resources;

use App\Facades\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseNpcBookPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'name' => (string) $this->resource->name,
            'lvl' => (int) ($this->resource->lvl ?? 0),
            'profession' => (string) ($this->resource->profession?->value ?? ''),
            'profession_name' => (string) ($this->resource->profession?->description() ?? ''),
            'rank' => (string) ($this->resource->rank?->value ?? ''),
            'rank_name' => (string) ($this->resource->rank?->description() ?? ''),
            'src' => AssetUrl::npc($this->resource->src),
        ];
    }
}


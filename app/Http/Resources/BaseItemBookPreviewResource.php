<?php

namespace App\Http\Resources;

use App\Facades\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseItemBookPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attributes = is_array($this->resource->attributes) ? $this->resource->attributes : [];

        return [
            'id' => (int) $this->resource->id,
            'name' => (string) $this->resource->name,
            'src' => AssetUrl::item($this->resource->src),
            'rarity' => (string) ($this->resource->rarity ?? ''),
            'category' => (string) ($this->resource->category?->value ?? ''),
            'category_name' => (string) ($this->resource->category?->description() ?? ''),
            'price' => (int) ($this->resource->price ?? 0),
            'attributes' => $attributes,
            'need_level' => (int) ($attributes['needLevel'] ?? 0),
        ];
    }
}


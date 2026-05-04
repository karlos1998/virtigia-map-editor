<?php

namespace App\Http\Resources;

use App\Facades\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'name' => (string) $this->resource->name,
            'path' => (string) $this->resource->path,
            'url' => AssetUrl::fromPath($this->resource->path),
            'created_at' => optional($this->resource->created_at)->toDateTimeString(),
            'updated_at' => optional($this->resource->updated_at)->toDateTimeString(),
        ];
    }
}

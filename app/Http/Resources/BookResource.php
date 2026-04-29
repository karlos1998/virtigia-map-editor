<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'title' => (string) $this->resource->title,
            'content' => (string) ($this->resource->content ?? ''),
            'created_at' => optional($this->resource->created_at)->toDateTimeString(),
            'updated_at' => optional($this->resource->updated_at)->toDateTimeString(),
        ];
    }
}

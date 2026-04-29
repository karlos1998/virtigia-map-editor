<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $content = (string) ($this->resource->content ?? '');

        return [
            'id' => (int) $this->resource->id,
            'title' => (string) $this->resource->title,
            'content_preview' => mb_strlen($content) > 120
                ? mb_substr($content, 0, 120).'...'
                : $content,
        ];
    }
}

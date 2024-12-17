<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DialogNodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'type' => $this->resource->type,
            'position' => $this->resource->position,
            $this->mergeWhen($this->resource->type == 'special', fn() => [
                'data' => [
                    'label' => 'Nazwa Npc',
                    'content' => $this->resource->content,
                    'options' => $this->resource->options,
                ]
            ])
        ];
    }
}

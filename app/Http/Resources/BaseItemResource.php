<?php

namespace App\Http\Resources;

use App\Models\BaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read BaseItem $resource
 */
class BaseItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'src' => $this->resource->src . '?' . $this->resource->updated_at->timestamp,
            $this->mergeWhen($this->resource->pivot?->position !== null, fn() => [
                'position' => $this->resource->pivot->position,
            ])
        ];
    }
}

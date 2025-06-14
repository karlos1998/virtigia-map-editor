<?php

namespace App\Http\Resources;

use App\Enums\Profession;
use App\Models\BaseItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\DialogResource;

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

            'src' => config('assets.url') . config('assets.dirs.items') . $this->resource->src . '?' . $this->resource->updated_at->timestamp,
//            'src' => Storage::disk('s3')->temporaryUrl('img' . config('assets.dirs.items') . $this->resource->src, now()->addHour()),
            $this->mergeWhen($this->resource->pivot?->position !== null, fn() => [
                'position' => $this->resource->pivot->position,
            ]),

            'category_name' => $this->resource->category?->description(),
            'currency_name' => $this->resource->currency?->description(),
            'currency' => $this->resource->currency,
            'need_professions' => array_map(fn($code) => Profession::tryFrom($code)?->description(), $this->resource->attributes['needProfessions'] ?? []),
            'need_level' => $this->resource->attributes['needLevel'] ?? null,

            'attributes' => $this->resource->attributes != null ? $this->resource->attributes : [],

            'in_use' => $this->resource->isInUse(),

            $this->mergeWhen($request->routeIs('base-items.index') || $request->routeIs('base-items.show'), function() {
              return [
                  'shops' => ShopResource::collection($this->resource->shops),
                  'baseNpcs' => BaseNpcResource::collection($this->resource->baseNpcs),
                  'dialogs' => DialogResource::collection($this->resource->dialogs()->get()),
              ];
            })
        ];
    }
}

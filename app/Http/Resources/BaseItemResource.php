<?php

namespace App\Http\Resources;

use App\Enums\Profession;
use App\Facades\AssetUrl;
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
        $usageView = $this->resource->relationLoaded('usageView') ? $this->resource->usageView : null;
        $usageSources = collect($usageView?->sources ?? [])
            ->map(function (array $source): array {
                if (isset($source['npc']) && is_array($source['npc'])) {
                    $source['npc']['src'] = AssetUrl::npc($source['npc']['src'] ?? null);
                }

                return $source;
            })
            ->values()
            ->all();

        return [
            ...parent::toArray($request),

            'src' => AssetUrl::item($this->resource->src),
            $this->mergeWhen($this->resource->pivot?->position !== null, fn () => [
                'position' => $this->resource->pivot->position,
            ]),

            'category_name' => $this->resource->category?->description(),
            'currency_name' => $this->resource->currency?->description(),
            'currency' => $this->resource->currency,
            'need_professions' => array_map(fn ($code) => Profession::tryFrom($code)?->description(), $this->resource->attributes['needProfessions'] ?? []),
            'need_level' => $this->resource->attributes['needLevel'] ?? null,

            'attributes' => $this->resource->attributes != null ? $this->resource->attributes : [],
            'attribute_points' => $this->resource->attribute_points ?? null,
            'manual_attribute_points' => $this->resource->manual_attribute_points ?? null,
            'reverse_attributes' => $this->resource->reverse_attributes ?? null,

            'in_use' => $usageView?->is_in_use ?? $this->resource->isInUse(),
            'usage_sources' => $usageSources,
            'usage_source_count' => $usageView?->source_count ?? 0,
            'specific_currency_price' => $this->resource->specific_currency_price, // specjalna cena waluty dedykowana dla itemu

            $this->mergeWhen($request->routeIs('base-items.show'), function () {
                return [
                    'shops' => ShopResource::collection($this->resource->shops),
                    'baseNpcs' => BaseNpcResource::collection($this->resource->baseNpcs),
                    'dialogs' => DialogResource::collection($this->resource->dialogs->unique()),
                ];
            }),
        ];
    }
}

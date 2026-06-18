<?php

namespace App\Http\Resources;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemRarity;
use App\Facades\AssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseItemDuplicateViewResource extends JsonResource
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
            'name' => $this->resource->name,
            'category' => $this->resource->category,
            'category_name' => BaseItemCategory::tryFrom((string) $this->resource->category)?->description(),
            'rarity' => $this->resource->rarity,
            'rarity_name' => BaseItemRarity::tryFrom((string) $this->resource->rarity)?->description(),
            'need_level' => $this->resource->need_level,
            'refreshed_at' => $this->resource->refreshed_at?->toDateTimeString(),
            'duplicate_item' => [
                'id' => $this->resource->duplicate_base_item_id,
                'name' => $this->resource->name,
                'src' => AssetUrl::item($this->resource->duplicate_src),
                'in_use' => false,
                'usage_source_count' => $this->resource->duplicate_usage_source_count,
            ],
            'used_item' => [
                'id' => $this->resource->used_base_item_id,
                'name' => $this->resource->name,
                'src' => AssetUrl::item($this->resource->used_src),
                'in_use' => true,
                'usage_source_count' => $this->resource->used_usage_source_count,
            ],
        ];
    }
}

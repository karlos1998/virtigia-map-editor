<?php

namespace App\Http\Resources;

use App\Models\Dialog;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Shop $resource
 */
class ShopResource extends JsonResource
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
            'binds_items_permanently' => $this->resource->binds_items_permanently,
            'buy_price_percent' => $this->resource->buy_price_percent,
            'sell_price_percent' => $this->resource->sell_price_percent,
            'max_buy_price' => $this->resource->max_buy_price,

            'items_count' => $this->resource->items()->count(),

            'dialogs_count' => $this->resource->dialogs()->count(),

//            'npcs' => $this->whenLoaded('dialogs', fn() => NpcResource::collection($this->resource->dialogs->flatMap(function (Dialog $dialog) {
//                return $dialog->npcs;
//            }))),
            'dialogs' => DialogResource::collection($this->resource->dialogs()->select('dialogs.*')->with('npcs')->get()),
            'npcs' => NpcResource::collection(\App\Models\Npc::where('dialog_id', '!=', null)->whereIn('dialog_id', $this->resource->dialogs->pluck('id'))->get()),
            'currency_item_id' => $this->resource->currency_item_id, // opcjonalne id przedmiotu jako waluta
            'currency_item' => $this->resource->currency_item_id ? new \App\Http\Resources\BaseItemResource($this->resource->currencyItem) : null, // pełny przedmiot jako waluta
        ];
    }
}

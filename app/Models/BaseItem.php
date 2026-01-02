<?php

namespace App\Models;

use App\Enums\BaseItemCategory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Engines\MeilisearchEngine;
//use Laravel\Scout\Searchable;

class BaseItem extends DynamicModel
{

    use SoftDeletes;
//    use Searchable;

    /**
     * Get the name of the index associated with the model.
     */
    public function searchableAs(): string
    {
        return 'base_items_' . $this->getConnectionName();
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

//    public function searchableUsing()
//    {
//        return app(MeiliSearchEngine::class)->setConnection('retro');
//    }

    protected $fillable = [
        'name',
        'src',
        'stats',
        'cl',
        'pr',
        'edited_manually',
        'attributes',
        'attribute_points',
        'manual_attribute_points',
        'reverse_attributes',
        'rarity',
        'category',
        'price',
        'currency',
        'specific_currency_price',
    ];

    protected $casts = [
        'attributes' => 'json',
        'attribute_points' => 'json',
        'manual_attribute_points' => 'json',
        'reverse_attributes' => 'json',
        'category' => BaseItemCategory::class,
        'edited_manually' => 'boolean',
        'currency' => \App\Enums\BaseItemCurrency::class
    ];

    public function shops()
    {
        return $this->belongsToMany(Shop::class, ShopItem::class, 'item_id', 'shop_id')
            ->withPivot('position');
    }

    public function baseNpcs()
    {
        return $this->belongsToMany(BaseNpc::class, BaseNpcLoot::class, 'base_item_id', 'base_npc_id');
    }

    public function isInUse()
    {
        return $this->shops()->exists() ||
            $this->baseNpcs()->exists() ||
            $this->dialogs()->exists();
    }

    /**
     * Get all dialogs where this item is used in dialog node options.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function dialogs()
    {
        return $this->belongsToMany(Dialog::class, 'dialog_nodes', 'id', 'source_dialog_id')
            ->distinct()
            ->whereRaw('1=0') // Start with an impossible condition
            ->orWhereHas('nodes.options', function ($query) {
                $query->whereRaw('JSON_CONTAINS(rules, ?, \'$.items.value\')', [$this->id]);
            })
            ->orWhereHas('nodes', function ($query) {
                $query->whereRaw('JSON_CONTAINS(additional_actions, ?, \'$.addItems.value\')', [$this->id]);
            });
    }
}

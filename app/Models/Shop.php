<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    protected $fillable = ['name', 'binds_items_permanently', 'buy_price_percent', 'sell_price_percent', 'max_buy_price', 'currency_item_id'];

    protected $casts = ['binds_items_permanently' => 'boolean'];

    public function items()
    {
        return $this->belongsToMany(BaseItem::class, ShopItem::class, 'shop_id', 'item_id')
            ->withPivot('position');
    }

    public function dialogs()
    {
        return $this
            ->belongsToMany(Dialog::class, DialogNode::class, 'shop_id', 'source_dialog_id')
            ->distinct(); // unikalny dialog, bo sklep moze byc wywolany przez wiele DialogNode
    }

    public function currencyItem()
    {
        return $this->belongsTo(BaseItem::class, 'currency_item_id');
    }
}

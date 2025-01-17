<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends DynamicModel
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function items()
    {
        return $this->belongsToMany(BaseItem::class, ShopItem::class, 'shop_id', 'item_id')
            ->withPivot('position');
    }

    public function dialogs()
    {
        return $this
            ->belongsToMany(Dialog::class, DialogNode::class, 'source_dialog_id', 'shop_id')
            ->distinct(); // unikalny dialog, bo sklep moze byc wywolany przez wiele DialogNode
    }
}

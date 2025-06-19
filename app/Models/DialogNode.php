<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DialogNode extends DynamicModel
{
    use HasFactory;

    protected $fillable = ['content', 'type', 'position', 'action_data', 'additional_actions'];

    protected $casts = [
        'position' => 'json',
        'action_data' => 'json',
        'additional_actions' => 'json',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(DialogNodeOption::class, 'node_id');
    }

    public function dialog(): HasOne
    {
        return $this->hasOne(Dialog::class, 'id', 'source_dialog_id');
    }

    public function shop() {
        return $this->hasOne(Shop::class, 'id', 'shop_id');
    }



    //tylko dla type start
    public function getEdges()
    {
        if($this->type != 'start') throw new \Exception('Próbowano pobrać edges do node który nie jest startoway');

        return DialogEdge::where('source_dialog_id', $this->source_dialog_id)->whereNull('source_option_id')->with('targetNode')->get();
    }
}

<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class StoreRenewableMapItemRequest extends FormRequest
{

    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'map_id' => ['required', "exists:{$this->selectedDatabase}.maps,id"],
            'base_item_id' => ['required', "exists:{$this->selectedDatabase}.base_items,id"],
            'x' => ['required', 'integer', 'min:0'],
            'y' => ['required', 'integer', 'min:0'],
            'respawn_time_seconds' => ['required', 'integer', 'min:1', 'max:604800'], // do tygodnia
        ];
    }
}

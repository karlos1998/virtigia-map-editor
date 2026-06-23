<?php

namespace App\Http\Requests;

class StoreRenewableMapItemRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'map_id' => ['required', $this->existsOnCurrentWorld('maps')],
            'base_item_id' => ['required', $this->existsOnCurrentWorld('base_items')],
            'x' => ['required', 'integer', 'min:0'],
            'y' => ['required', 'integer', 'min:0'],
            'respawn_time_seconds' => ['required', 'integer', 'min:1', 'max:604800'], // do tygodnia
        ];
    }
}

<?php

namespace App\Http\Requests;

class StoreHotelRoomRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_item_id' => ['required', 'integer', $this->existsOnCurrentWorld('base_items')],
            'price' => ['required', 'integer', 'min:0'],
            'door_id' => [
                'required',
                'integer',
                $this->existsOnCurrentWorld('doors'),
                $this->uniqueOnCurrentWorld('hotel_rooms', 'door_id')
                    ->where('hotel_id', (int) $this->route('hotel')?->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'door_id.unique' => 'Ten pokój jest już dodany do tego hotelu.',
        ];
    }
}

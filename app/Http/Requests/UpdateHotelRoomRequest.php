<?php

namespace App\Http\Requests;

class UpdateHotelRoomRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelRoomId = (int) $this->route('hotelRoom')?->id;

        return [
            'base_item_id' => ['required', 'integer', $this->existsOnCurrentWorld('base_items')],
            'price' => ['required', 'integer', 'min:0'],
            'door_id' => [
                'required',
                'integer',
                $this->existsOnCurrentWorld('doors'),
                $this->uniqueOnCurrentWorld('hotel_rooms', 'door_id')
                    ->where('hotel_id', (int) $this->route('hotel')?->id)
                    ->ignore($hotelRoomId),
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

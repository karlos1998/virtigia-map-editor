<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHotelRoomRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hotelRoomId = (int) $this->route('hotelRoom')?->id;

        return [
            'base_item_id' => ['required', 'integer', "exists:{$this->selectedDatabase}.base_items,id"],
            'door_id' => [
                'required',
                'integer',
                "exists:{$this->selectedDatabase}.doors,id",
                Rule::unique("{$this->selectedDatabase}.hotel_rooms", 'door_id')
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

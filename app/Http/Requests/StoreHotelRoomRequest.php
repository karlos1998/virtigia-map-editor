<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelRoomRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_item_id' => ['required', 'integer', "exists:{$this->selectedDatabase}.base_items,id"],
            'door_id' => [
                'required',
                'integer',
                "exists:{$this->selectedDatabase}.doors,id",
                Rule::unique("{$this->selectedDatabase}.hotel_rooms", 'door_id')
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

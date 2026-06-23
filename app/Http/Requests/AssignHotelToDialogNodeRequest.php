<?php

namespace App\Http\Requests;

class AssignHotelToDialogNodeRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', $this->existsOnCurrentWorld('hotels')],
        ];
    }
}

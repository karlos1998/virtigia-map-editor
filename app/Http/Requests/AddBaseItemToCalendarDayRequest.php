<?php

namespace App\Http\Requests;

class AddBaseItemToCalendarDayRequest extends CurrentWorldRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendarDayId' => ['required', 'integer', $this->existsOnCurrentWorld('calendar_days')],
            'baseItemId' => ['required', 'integer', $this->existsOnCurrentWorld('base_items')],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

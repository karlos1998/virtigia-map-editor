<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class AddBaseItemToCalendarDayRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'calendarDayId' => ['required', 'integer', "exists:$this->selectedDatabase.calendar_days,id"],
            'baseItemId' => ['required', 'integer', "exists:$this->selectedDatabase.base_items,id"],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

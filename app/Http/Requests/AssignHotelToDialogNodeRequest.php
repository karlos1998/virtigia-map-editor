<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class AssignHotelToDialogNodeRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hotel_id' => ['required', 'integer', "exists:$this->selectedDatabase.hotels,id"],
        ];
    }
}

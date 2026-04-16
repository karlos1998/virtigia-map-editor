<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCurrency;
use App\Enums\HotelPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:100'],
            'currency' => ['required', Rule::enum(BaseItemCurrency::class)],
            'period' => ['required', Rule::enum(HotelPeriod::class)],
        ];
    }
}

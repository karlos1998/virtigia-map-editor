<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'buy_price_percent' => 'required|integer|min:0|max:100',
            'sell_price_percent' => 'required|integer|min:100|max:1000',
            'max_buy_price' => 'required|integer|min:0|max:1000000',
        ];
    }
}

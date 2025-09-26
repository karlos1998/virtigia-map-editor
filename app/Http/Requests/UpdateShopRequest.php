<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class UpdateShopRequest extends FormRequest
{
    use LoadCurrentWorldTemplate;
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
            'currency_item_id' => "nullable|integer|exists:{$this->selectedDatabase}.base_items,id",
        ];
    }
}

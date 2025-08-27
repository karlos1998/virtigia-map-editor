<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateBaseItemRequest extends FormRequest
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
            'name' => [
                'required',
                'min:4',
                'max:50',
            ],

            'category' => [
                'required',
                new Enum(BaseItemCategory::class),
            ],

            'rarity' => [
                'required',
                new Enum(BaseItemRarity::class),
            ],

            'price' => [
                'required',
                'integer',
                'max:1000000000'
            ],

            'currency' => [
                'required',
                new Enum(BaseItemCurrency::class),
            ],
        ];
    }
}

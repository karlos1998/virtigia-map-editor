<?php

namespace App\Http\Requests;

class BulkAttachBaseItemsToShopRequest extends CurrentWorldRequest
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
            'shop_id' => ['required', 'integer', $this->existsOnCurrentWorld('shops')],
            'start_position' => ['required', 'integer', 'min:0', 'max:79'],
            'item_ids' => ['required', 'array', 'min:1', 'max:80'],
            'item_ids.*' => ['integer', 'distinct', $this->existsOnCurrentWorld('base_items')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shop_id.required' => 'Wybierz sklep.',
            'start_position.required' => 'Podaj pozycję początkową.',
            'start_position.max' => 'Pozycja w sklepie musi mieścić się w zakresie 0–79.',
            'item_ids.required' => 'Wybierz przynajmniej jeden przedmiot.',
            'item_ids.max' => 'Jednorazowo można przypisać maksymalnie 80 przedmiotów.',
        ];
    }
}

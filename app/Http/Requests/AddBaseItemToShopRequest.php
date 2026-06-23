<?php

namespace App\Http\Requests;

class AddBaseItemToShopRequest extends CurrentWorldRequest
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
            'baseItemId' => ['required', 'integer', $this->existsOnCurrentWorld('base_items')],
            'position' => [
                'required',
                'integer',
                'between:0,79',
                function ($attribute, $value, $fail) {
                    if ($this->shop->items()->wherePivot('position', $value)->exists()) {
                        $fail('The position is already occupied.');
                    }
                },
            ],
        ];
    }
}

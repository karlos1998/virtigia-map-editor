<?php


namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;

class AddBaseItemToShopRequest extends FormRequest
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
            'baseItemId' => ['required', 'integer', "exists:$this->selectedDatabase.base_items,id"],
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

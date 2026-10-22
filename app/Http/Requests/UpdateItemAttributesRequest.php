<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCategory;
use App\Models\BaseItem;
use App\Rules\PlayingCardAttributes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemAttributesRequest extends FormRequest
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
        $item = $this->route('baseItem');
        $isCard = $item instanceof BaseItem && $item->category === BaseItemCategory::PLAYING_CARDS;

        return [
            'attributes.*' => ['nullable'],
            'attributes.playingCard' => [$isCard ? 'required' : 'sometimes', new PlayingCardAttributes],
            'attributes' => [
                'required',
                'array',
            ],
            'attribute_points' => [
                'nullable',
                'array',
            ],
            'manual_attribute_points' => [
                'nullable',
                'array',
            ],
        ];
    }
}

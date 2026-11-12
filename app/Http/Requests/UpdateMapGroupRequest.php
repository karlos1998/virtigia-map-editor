<?php

namespace App\Http\Requests;

use App\Enums\MapGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateMapGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'map_group' => ['required', new Enum(MapGroup::class)],
        ];
    }

    public function mapGroup(): MapGroup
    {
        return MapGroup::from($this->input('map_group'));
    }
}

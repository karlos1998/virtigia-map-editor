<?php

namespace App\Http\Requests;

use App\Enums\Profession;
use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpawnPointRequest extends FormRequest
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
            'map_id' => ['required', "exists:$this->selectedDatabase.maps,id"],
            'x' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $map = Map::find($this->map_id);
                    if ($map && $value > $map->x) {
                        $fail("The $attribute must not exceed the map's width ({$map->x}).");
                    }
                },
            ],
            'y' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $map = Map::find($this->map_id);
                    if ($map && $value > $map->y) {
                        $fail("The $attribute must not exceed the map's height ({$map->y}).");
                    }
                },
            ],
            'profession' => [
                'required',
                Rule::in(Profession::values())
            ],
        ];
    }
}

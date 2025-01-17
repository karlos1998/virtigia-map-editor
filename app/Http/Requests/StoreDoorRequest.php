<?php

namespace App\Http\Requests;

use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;

class StoreDoorRequest extends FormRequest
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

        $map = Map::findOrFail($this->input('map_id'));

        $goMap = Map::findOrFail($this->input('go_map_id'));

        return [


            //todo - sprwadzenie czy na tych pierwszych koordach juz czgos nie ma typu drzwi albo npc

            'map_id' => ['required', 'exists:retro.maps,id'],
            'x' => ['required', 'integer', 'min:0', 'max:' . ($map->x - 1)],
            'y' => ['required', 'integer', 'min:0', 'max:' . ($map->y - 1)],

            'go_map_id' => ['required', 'exists:retro.maps,id'],
            'go_x' => ['required', 'integer', 'min:0', 'max:' . ($goMap->x - 1)],
            'go_y' => ['required', 'integer', 'min:0', 'max:' . ($goMap->y - 1)]
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\Map;
use App\Models\Door;
use App\Models\NpcLocation;
use Illuminate\Foundation\Http\FormRequest;

class StoreDoorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $map = Map::findOrFail($this->input('map_id'));
        $goMap = Map::findOrFail($this->input('go_map_id'));

        return [
            'map_id' => [
                'required',
                'exists:retro.maps,id',
                function ($attribute, $value, $fail) {
                    if (
                        Door::where('map_id', $value)
                            ->where('x', $this->input('x'))
                            ->where('y', $this->input('y'))
                            ->exists()
                        ||
                        NpcLocation::where('map_id', $this->input('map_id'))
                            ->where('x', $this->input('x'))
                            ->where('y', $this->input('y'))
                            ->exists()
                    ) {
                        $fail('Na tej lokacji istnieje już obiekt.');
                    }
                },
            ],
            'x' => [
                'required',
                'integer',
                'min:0',
                'max:' . ($map->x - 1),
            ],
            'y' => [
                'required',
                'integer',
                'min:0',
                'max:' . ($map->y - 1)
            ],



            'go_map_id' => ['required', 'exists:retro.maps,id'],
            'go_x' => [
                'required',
                'integer',
                'min:0',
                'max:' . ($goMap->x - 1)
            ],
            'go_y' => [
                'required',
                'integer',
                'min:0',
                'max:' . ($goMap->y - 1)
            ]
        ];
    }
}

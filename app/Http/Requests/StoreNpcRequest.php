<?php

namespace App\Http\Requests;

use App\Http\Requests\Traits\LoadCurrentWorldTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\NpcLocation;
use App\Models\Map;

class StoreNpcRequest extends FormRequest
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
            'npc' => [
                'required',
                "exists:$this->selectedDatabase.base_npcs,id",
            ],
            'location' => [
                'required',
                'array',
                function ($attribute, $value, $fail) {
                    $mapId = $value['mapId'] ?? null;
                    $x = $value['x'] ?? null;
                    $y = $value['y'] ?? null;

                    if ($mapId !== null && $x !== null && $y !== null) {
                        $exists = NpcLocation::where('map_id', $mapId)
                            ->where('x', $x)
                            ->where('y', $y)
                            ->exists();

                        if ($exists) {
                            $fail("The NPC location ($mapId, $x, $y) already exists in the database.");
                        }
                    }
                },
            ],

            'location.mapId' => [
                'required',
                "exists:$this->selectedDatabase.maps,id",
                function ($attribute, $value, $fail) {
                    $map = Map::find($value);
                    if ($map) {
                        $locationX = $this->input('location.x');
                        $locationY = $this->input('location.y');

                        if ($locationX > $map->x || $locationY > $map->y) {
                            $fail("The $attribute coordinates exceed the map boundaries (max_x: {$map->x}, max_y: {$map->y}).");
                        }
                    }
                },
            ],

            'location.x' => [
                'required',
                'integer',
                'min:0',
            ],

            'location.y' => [
                'required',
                'integer',
                'min:0',
            ],

        ];
    }
}

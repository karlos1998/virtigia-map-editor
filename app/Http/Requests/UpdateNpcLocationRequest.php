<?php

namespace App\Http\Requests;

use App\Models\Door;
use App\Models\Map;
use App\Models\NpcLocation;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNpcLocationRequest extends FormRequest
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
        //todo - validacja sie powtarza, np przy drzwiach, przy dowaniu npc tez powinna byc.. trzeba pod to zrobic jakas regule.


        $map = Map::findOrFail($this->input('map_id'));

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

        ];
    }
}

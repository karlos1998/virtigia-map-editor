<?php

namespace App\Http\Requests;

use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDialogNodeActionDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->dialogNode->type !== 'teleportation') {
            return [];
        }

        /**
         * TODO: na dole na pale jest napisane retro
         */

        $mapId = $this->input('teleportation.mapId');
        $map = $mapId ? Map::find($mapId) : null;
        $maxX = $map?->x ? $map->x - 1 : 0;
        $maxY = $map?->y ? $map->y - 1 : 0;

        return [
            'teleportation' => ['required', 'array'],
            'teleportation.mapId' => ['required', 'exists:retro.maps,id'],
            'teleportation.x' => ['required', 'integer', 'min:0', 'max:' . $maxX],
            'teleportation.y' => ['required', 'integer', 'min:0', 'max:' . $maxY]
        ];
    }
}

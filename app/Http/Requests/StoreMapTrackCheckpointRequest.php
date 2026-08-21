<?php

namespace App\Http\Requests;

use App\Models\Map;
use Illuminate\Validation\Validator;

class StoreMapTrackCheckpointRequest extends CurrentWorldRequest
{
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'map_id' => ['required', 'integer', $this->existsOnCurrentWorld('maps')],
            'tiles' => ['required', 'array', 'min:1', 'max:2000'],
            'tiles.*.x' => ['required', 'integer', 'min:0'],
            'tiles.*.y' => ['required', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $map = Map::query()->find($this->integer('map_id'));
                if (! $map) {
                    return;
                }

                $seen = [];
                foreach ($this->input('tiles', []) as $index => $tile) {
                    $x = (int) $tile['x'];
                    $y = (int) $tile['y'];
                    $key = "{$x}:{$y}";

                    if (isset($seen[$key])) {
                        $validator->errors()->add("tiles.{$index}", 'Ta kratka została wskazana więcej niż raz.');
                    }
                    $seen[$key] = true;

                    if ($x >= $map->x || $y >= $map->y) {
                        $validator->errors()->add("tiles.{$index}", 'Kratka znajduje się poza obszarem mapy.');

                        continue;
                    }

                    $collisionIndex = $y * $map->x + $x;
                    if ((((string) $map->col)[$collisionIndex] ?? '0') === '1') {
                        $validator->errors()->add("tiles.{$index}", 'Checkpoint nie może znajdować się na kolizji.');
                    }
                }
            },
        ];
    }
}

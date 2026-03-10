<?php

namespace App\Http\Requests\Api;

use App\Models\BaseNpc;
use App\Models\Door;
use App\Models\Map;
use App\Models\NpcLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApiNpcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_npc_id' => ['required', 'integer', Rule::exists(BaseNpc::class, 'id')],
            'map_id' => ['required', 'integer', Rule::exists(Map::class, 'id')],
            'x' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $map = Map::query()->find($this->integer('map_id'));

                    if ($map !== null && $value > ($map->x - 1)) {
                        $fail("Pole {$attribute} wykracza poza szerokość mapy.");
                    }
                },
            ],
            'y' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $map = Map::query()->find($this->integer('map_id'));

                    if ($map !== null && $value > ($map->y - 1)) {
                        $fail("Pole {$attribute} wykracza poza wysokość mapy.");
                    }
                },
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasAny(['map_id', 'x', 'y'])) {
                return;
            }

            $mapId = $this->integer('map_id');
            $x = $this->integer('x');
            $y = $this->integer('y');

            if (
                Door::query()->where('map_id', $mapId)->where('x', $x)->where('y', $y)->exists() ||
                NpcLocation::query()->where('map_id', $mapId)->where('x', $x)->where('y', $y)->exists()
            ) {
                $validator->errors()->add('map_id', 'Na tej lokacji istnieje już obiekt.');
            }
        });
    }
}

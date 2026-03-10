<?php

namespace App\Http\Requests\Api;

use App\Enums\PvpType;
use App\Models\Map;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateApiMapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $map = Map::query()->find((int) $this->route('mapId'));
        $expectedLength = $map !== null ? $map->x * $map->y : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'pvp' => ['sometimes', new Enum(PvpType::class)],
            'col' => [
                'sometimes',
                'string',
                'regex:/^[01]+$/',
                ...(is_int($expectedLength) ? ['size:'.$expectedLength] : []),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasAny(['name', 'pvp', 'col'])) {
                $validator->errors()->add('payload', 'Przekaż przynajmniej jedno pole: name, pvp albo col.');
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\BaseNpcCategory;
use App\Enums\BaseNpcRank;
use App\Enums\Profession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateBaseNpcRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'min:3',
                'max:50',
            ],
            'lvl' => [
                'required',
                'integer',
                'min:0',
                'max:1000',
            ],
            'rank' => [
                'required',
                new Enum(BaseNpcRank::class),
            ],
            'category' => [
                'required',
                new Enum(BaseNpcCategory::class),
            ],
            'profession' => [
                'required',
                new Enum(Profession::class),
            ],
            'is_aggressive' => [
                'boolean',
            ],
            'divine_intervention' => [
                'nullable',
                'boolean',
            ],
            'drop_chances' => [
                'nullable',
                'array',
                'size:5',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $dropChanceSum = array_sum($value);

                    if (abs($dropChanceSum - 1) > 0.0001) {
                        $fail('Suma wszystkich szans dropu musi wynosić 1.');
                    }
                },
            ],
            'drop_chances.*' => [
                'required',
                'numeric',
                'between:0,1',
            ],
            'min_respawn_time' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'max_respawn_time' => [
                'nullable',
                'integer',
                'min:0',
                'gte:min_respawn_time',
            ]
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'drop_chances.array' => 'Szanse dropu muszą być tablicą wartości.',
            'drop_chances.size' => 'Szanse dropu muszą zawierać dokładnie 5 wartości.',
            'drop_chances.*.required' => 'Każda szansa dropu jest wymagana.',
            'drop_chances.*.numeric' => 'Każda szansa dropu musi być liczbą.',
            'drop_chances.*.between' => 'Każda szansa dropu musi mieścić się w zakresie od 0 do 1.',
        ];
    }
}

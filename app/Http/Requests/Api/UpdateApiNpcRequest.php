<?php

namespace App\Http\Requests\Api;

use App\Enums\BaseNpcRank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateApiNpcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:50'],
            'lvl' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'rank' => ['sometimes', new Enum(BaseNpcRank::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasAny(['name', 'lvl', 'rank'])) {
                $validator->errors()->add('payload', 'Przekaż przynajmniej jedno pole: name, lvl albo rank.');
            }
        });
    }
}

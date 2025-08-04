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
}

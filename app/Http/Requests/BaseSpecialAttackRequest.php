<?php

namespace App\Http\Requests;

use App\Enums\SpecialAttackEffectType;
use App\Enums\SpecialAttackElement;
use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

abstract class BaseSpecialAttackRequest extends FormRequest
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
                'string',
                'max:255',
            ],
            'attack_type' => [
                'required',
                new Enum(SpecialAttackType::class),
            ],
            'charge_turns' => [
                'required',
                'integer',
                'min:0',
            ],
            'target' => [
                'required',
                new Enum(SpecialAttackTarget::class),
            ],
            'random_target' => [
                'required',
                'boolean',
            ],
            'effects' => [
                'nullable',
                'array',
            ],
            'effects.*.type' => [
                'required_with:effects',
                new Enum(SpecialAttackEffectType::class),
            ],
            'effects.*.value' => [
                'required_with:effects',
                'numeric',
            ],
            'effects.*.duration' => [
                'required_with:effects',
                'integer',
                'min:0',
            ],
            'damages' => [
                'nullable',
                'array',
            ],
            'damages.*.element' => [
                'required_with:damages',
                new Enum(SpecialAttackElement::class),
            ],
            'damages.*.min_damage' => [
                'required_with:damages',
                'integer',
                'min:0',
            ],
            'damages.*.max_damage' => [
                'required_with:damages',
                'integer',
                'min:0',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nazwa ciosu specjalnego jest wymagana.',
            'name.max' => 'Nazwa ciosu specjalnego nie może przekraczać 255 znaków.',
            'attack_type.required' => 'Typ ataku jest wymagany.',
            'charge_turns.required' => 'Liczba tur ładowania jest wymagana.',
            'charge_turns.min' => 'Liczba tur ładowania nie może być ujemna.',
            'target.required' => 'Cel ataku jest wymagany.',
            'random_target.required' => 'Informacja o losowym celu jest wymagana.',
            'effects.*.type.required_with' => 'Typ efektu jest wymagany.',
            'effects.*.value.required_with' => 'Wartość efektu jest wymagana.',
            'effects.*.duration.required_with' => 'Czas trwania efektu jest wymagany.',
            'effects.*.duration.min' => 'Czas trwania efektu nie może być ujemny.',
            'damages.*.element.required_with' => 'Element obrażeń jest wymagany.',
            'damages.*.min_damage.required_with' => 'Minimalne obrażenia są wymagane.',
            'damages.*.min_damage.min' => 'Minimalne obrażenia nie mogą być ujemne.',
            'damages.*.max_damage.required_with' => 'Maksymalne obrażenia są wymagane.',
            'damages.*.max_damage.min' => 'Maksymalne obrażenia nie mogą być ujemne.',
        ];
    }
}

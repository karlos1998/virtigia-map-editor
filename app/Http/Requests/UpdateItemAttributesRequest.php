<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateItemAttributesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $attributes = $this->input('attributes');

        if (! is_array($attributes) || ! array_key_exists('healChanceAfterFight', $attributes)) {
            return;
        }

        $healing = $attributes['healChanceAfterFight'];
        $isEmptyPair = is_array($healing)
            && array_is_list($healing)
            && count($healing) <= 2
            && array_filter($healing, fn (mixed $value): bool => $value !== null && $value !== '') === [];

        if ($healing === null || $healing === '' || $isEmptyPair) {
            unset($attributes['healChanceAfterFight']);
            $this->merge(['attributes' => $attributes]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'suggest_destroy_on_level_gap' => ['sometimes', 'boolean'],
            'attributes' => [
                'present',
                'array',
            ],
            'attributes.combatSelfHeal' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'attributes.openShop' => ['sometimes', 'array:id,name', 'required_array_keys:id,name'],
            'attributes.openShop.id' => ['required_with:attributes.openShop', 'integer', 'min:1'],
            'attributes.openShop.name' => ['required_with:attributes.openShop', 'string'],
            'attributes.healChanceAfterFight' => ['sometimes', 'array', 'list', 'size:2'],
            'attributes.healChanceAfterFight.0' => ['required_with:attributes.healChanceAfterFight', 'integer', 'min:1', 'max:99'],
            'attributes.healChanceAfterFight.1' => ['required_with:attributes.healChanceAfterFight', 'integer', 'min:1'],
            'attribute_points' => [
                'nullable',
                'array',
            ],
            'manual_attribute_points' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'attributes.healChanceAfterFight' => 'leczenie po walce',
            'attributes.healChanceAfterFight.0' => 'szansa na leczenie po walce (%)',
            'attributes.healChanceAfterFight.1' => 'liczba punktów leczenia po walce',
        ];
    }

    public function messages(): array
    {
        return [
            'attributes.healChanceAfterFight.array' => 'Leczenie po walce musi zawierać szansę i liczbę punktów leczenia.',
            'attributes.healChanceAfterFight.list' => 'Leczenie po walce musi zawierać szansę i liczbę punktów leczenia.',
            'attributes.healChanceAfterFight.size' => 'Leczenie po walce musi zawierać dokładnie dwie wartości.',
            'attributes.healChanceAfterFight.0.required_with' => 'Podaj szansę na leczenie po walce (1–99%).',
            'attributes.healChanceAfterFight.0.integer' => 'Szansa na leczenie po walce musi być liczbą całkowitą od 1 do 99.',
            'attributes.healChanceAfterFight.0.min' => 'Szansa na leczenie po walce musi wynosić od 1 do 99%.',
            'attributes.healChanceAfterFight.0.max' => 'Szansa na leczenie po walce musi wynosić od 1 do 99%.',
            'attributes.healChanceAfterFight.1.required_with' => 'Podaj liczbę punktów leczenia po walce.',
            'attributes.healChanceAfterFight.1.integer' => 'Liczba punktów leczenia po walce musi być dodatnią liczbą całkowitą.',
            'attributes.healChanceAfterFight.1.min' => 'Liczba punktów leczenia po walce musi być większa od zera.',
        ];
    }
}

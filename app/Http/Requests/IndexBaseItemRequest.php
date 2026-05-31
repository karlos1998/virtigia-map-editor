<?php

namespace App\Http\Requests;

use App\Enums\LegendaryBonus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBaseItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:255'],
            'legendary_bonus' => ['nullable', 'string', Rule::in(LegendaryBonus::valuesToList())],
        ];
    }

    /**
     * @return array{description: string|null, legendary_bonus: string|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        $description = trim((string) ($validated['description'] ?? ''));
        $legendaryBonus = trim((string) ($validated['legendary_bonus'] ?? ''));

        return [
            'description' => $description !== '' ? $description : null,
            'legendary_bonus' => $legendaryBonus !== '' ? $legendaryBonus : null,
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\LegendaryBonus;
use Illuminate\Validation\Rule;

class IndexBaseItemRequest extends CurrentWorldRequest
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
            'attribute_keys' => ['nullable', 'array', 'max:60'],
            'attribute_keys.*' => ['string', 'distinct', 'max:80', 'regex:/\A[A-Za-z][A-Za-z0-9_]*\z/'],
            'shop_ids' => ['nullable', 'array', 'max:50'],
            'shop_ids.*' => ['integer', 'distinct', $this->existsOnCurrentWorld('shops')],
        ];
    }

    /**
     * @return array{description: string|null, legendary_bonus: string|null, attribute_keys: array<int, string>, shop_ids: array<int, int>}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        $description = trim((string) ($validated['description'] ?? ''));
        $legendaryBonus = trim((string) ($validated['legendary_bonus'] ?? ''));
        $attributeKeys = collect($validated['attribute_keys'] ?? [])
            ->map(fn (string $attributeKey): string => trim($attributeKey))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $shopIds = collect($validated['shop_ids'] ?? [])
            ->map(fn (int|string $shopId): int => (int) $shopId)
            ->unique()
            ->values()
            ->all();

        return [
            'description' => $description !== '' ? $description : null,
            'legendary_bonus' => $legendaryBonus !== '' ? $legendaryBonus : null,
            'attribute_keys' => $attributeKeys,
            'shop_ids' => $shopIds,
        ];
    }
}

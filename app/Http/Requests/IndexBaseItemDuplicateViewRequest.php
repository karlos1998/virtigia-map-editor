<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemRarity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBaseItemDuplicateViewRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', Rule::in(BaseItemCategory::valuesToList())],
            'rarity' => ['nullable', 'string', Rule::in(BaseItemRarity::valuesToList())],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    /**
     * @return array{search: string|null, category: string|null, rarity: string|null}
     */
    public function filters(): array
    {
        $validated = $this->validated();

        $search = trim((string) ($validated['search'] ?? ''));
        $category = trim((string) ($validated['category'] ?? ''));
        $rarity = trim((string) ($validated['rarity'] ?? ''));

        return [
            'search' => $search !== '' ? $search : null,
            'category' => $category !== '' ? $category : null,
            'rarity' => $rarity !== '' ? $rarity : null,
        ];
    }

    public function perPage(): int
    {
        return max(10, min(100, (int) $this->integer('per_page', 50)));
    }
}

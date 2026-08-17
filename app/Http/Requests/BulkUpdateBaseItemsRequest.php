<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class BulkUpdateBaseItemsRequest extends CurrentWorldRequest
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
        $operation = $this->string('operation')->toString();

        return [
            'item_ids' => ['required', 'array', 'min:1', 'max:500'],
            'item_ids.*' => ['integer', 'distinct', $this->existsOnCurrentWorld('base_items')],
            'operation' => ['required', Rule::in(['binding', 'rarity', 'currency', 'price'])],
            'value' => match ($operation) {
                'binding' => ['required', Rule::in([
                    'isBoundToOwner',
                    'isPermanentlyBounded',
                    'isBindsAfterEquip',
                ])],
                'rarity' => ['required', new Enum(BaseItemRarity::class)],
                'currency' => ['required', new Enum(BaseItemCurrency::class)],
                default => ['nullable'],
            },
            'prices' => [Rule::requiredIf($operation === 'price'), 'array', 'max:500'],
            'prices.*.item_id' => [
                'required',
                'integer',
                'distinct',
                Rule::in($this->input('item_ids', [])),
            ],
            'prices.*.price' => ['required', 'integer', 'min:0', 'max:1000000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item_ids.required' => 'Wybierz przynajmniej jeden przedmiot.',
            'item_ids.min' => 'Wybierz przynajmniej jeden przedmiot.',
            'operation.required' => 'Wybierz operację masową.',
            'value.required' => 'Wybierz wartość do ustawienia.',
            'prices.required' => 'Nie udało się wyliczyć wartości wybranych przedmiotów.',
            'prices.*.item_id.in' => 'Cena dotyczy przedmiotu spoza zaznaczenia.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $prices = $this->input('prices', []);

            if (! is_array($prices) || $prices === []) {
                return;
            }

            if ($this->string('operation')->toString() === 'binding') {
                $validator->errors()->add('prices', 'Zmiana związania nie może modyfikować cen.');

                return;
            }

            $selectedItemIds = collect($this->input('item_ids', []))
                ->filter(fn ($itemId): bool => is_numeric($itemId))
                ->map(fn ($itemId): int => (int) $itemId)
                ->sort()
                ->values();
            $pricedItemIds = collect($prices)
                ->pluck('item_id')
                ->filter(fn ($itemId): bool => is_numeric($itemId))
                ->map(fn ($itemId): int => (int) $itemId)
                ->sort()
                ->values();

            if ($selectedItemIds->all() !== $pricedItemIds->all()) {
                $validator->errors()->add('prices', 'Cena musi zostać wyliczona dla każdego zaznaczonego przedmiotu.');
            }
        });
    }
}

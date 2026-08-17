<?php

namespace App\Http\Requests;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemCurrency;
use App\Enums\BaseItemRarity;
use App\Enums\LegendaryBonus;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class BulkUpdateBaseItemsRequest extends CurrentWorldRequest
{
    private const BOOLEAN_ATTRIBUTES = [
        'isNonStoreableInClanDeposit',
        'isBindPermanentlyAfterBuy',
        'isNonStoreableInDeposit',
        'isNotAuctionable',
        'unbindsOwnerBound',
        'unbindsPermanentlyBound',
        'isRecovered',
        'isUnidentified',
        'findHeroNpc',
        'findDetailedHeroNpc',
        'combatFlee',
        'openDeposit',
        'openClanDeposit',
        'openMail',
        'openAuction',
        'impossibleToRemove',
    ];

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
            'operation' => ['required', Rule::in([
                'binding',
                'boolean_attribute',
                'category',
                'clear_attributes',
                'currency',
                'legendary_bonus',
                'lifespan',
                'name',
                'price',
                'rarity',
                'required_level',
                'specific_currency_price',
            ])],
            'value' => match ($operation) {
                'binding' => ['required', Rule::in([
                    'none',
                    'isBoundToOwner',
                    'isPermanentlyBounded',
                    'isBindsAfterEquip',
                ])],
                'boolean_attribute' => ['required', Rule::in(self::BOOLEAN_ATTRIBUTES)],
                'category' => ['required', new Enum(BaseItemCategory::class)],
                'rarity' => ['required', new Enum(BaseItemRarity::class)],
                'currency' => ['required', new Enum(BaseItemCurrency::class)],
                'legendary_bonus' => ['required', Rule::in([
                    'none',
                    ...LegendaryBonus::valuesToList(),
                ])],
                'required_level' => ['required', 'integer', 'min:1', 'max:300'],
                'specific_currency_price' => ['present', 'nullable', 'integer', 'min:0', 'max:1000000'],
                'lifespan' => ['present', 'nullable', 'integer', 'min:0'],
                default => ['nullable'],
            },
            'enabled' => $operation === 'boolean_attribute'
                ? ['required', 'boolean']
                : ['nullable', 'boolean'],
            'attribute_key' => $operation === 'lifespan'
                ? ['required', Rule::in(['expiresOn', 'timeToDisappear'])]
                : ['nullable'],
            'attribute_paths' => $operation === 'clear_attributes'
                ? ['required', 'array', 'min:1', 'max:100']
                : ['nullable', 'array'],
            'attribute_paths.*' => [
                'string',
                'distinct',
                'regex:/^(attributes|attribute_points|manual_attribute_points):[A-Za-z][A-Za-z0-9_]*$/',
            ],
            'name_mode' => $operation === 'name'
                ? ['required', Rule::in(['replace', 'prefix', 'suffix'])]
                : ['nullable'],
            'search_phrase' => [Rule::requiredIf($operation === 'name' && $this->input('name_mode') === 'replace'), 'nullable', 'string', 'min:1', 'max:50'],
            'replacement_phrase' => match (true) {
                $operation === 'name' && $this->input('name_mode') === 'replace' => ['present', 'nullable', 'string', 'max:50'],
                $operation === 'name' => ['required', 'string', 'min:1', 'max:50'],
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

            if (! in_array($this->string('operation')->toString(), [
                'category',
                'currency',
                'price',
                'rarity',
                'required_level',
            ], true)) {
                $validator->errors()->add('prices', 'Ta operacja nie może modyfikować cen.');

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

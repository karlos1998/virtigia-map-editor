<?php

namespace App\Services;

use App\Models\BaseItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class BaseItemAttributeReverseService
{
    private const array ATTRIBUTE_POINTS = [
        'armor', 'criticalChance', 'allBaseAttributes', 'attackSpeed', 'armorDest', 'absorption',
        'absDest', 'fireResist', 'frostResist', 'lightResist', 'poisonResist', 'resistDest',
        'critReduction', 'physicalCriticStrength', 'magicalCriticStrength', 'strength', 'agility',
        'intellect', 'energy', 'energyDest', 'mana', 'manaDest', 'health', 'healing', 'evasion',
        'evasionReduction', 'block', 'attackSpeedReduction',
    ];

    private const array SCALED_ATTRIBUTES = [
        'criticalChance', 'allBaseAttributes', 'attackSpeed', 'defense', 'defenseDestroy',
        'physicalDamageAbsorption', 'magicalDamageAbsorption', 'absorptionDestroy',
        'fireResistance', 'frostResistance', 'lightResistance', 'poisonResistance',
        'magicalResistanceReduction', 'criticalReductionDuringDefending', 'physicalCritPower',
        'magicalCritPower', 'strength', 'agility', 'intellect', 'energy', 'energyDestroy',
        'mana', 'enemyManaReduction', 'health', 'combatHealthRestoration', 'evadePoints',
        'enemyEvasionReduction', 'blockPoints', 'enemyAttackSpeedReduction', 'chanceToCounter',
        'armorPuncture', 'chanceToBlockPuncture', 'deepWoundChance', 'healthPerStrength',
        'physicalDamage', 'arrowPhysicalDamage', 'poisonDamage', 'fireDamage', 'lightDamage', 'frostDamage',
    ];

    public function __construct(private readonly ApiAttributePointService $apiService) {}

    /** @return array<string, mixed> */
    public function preview(BaseItem $baseItem, int $userId): array
    {
        $this->ensureMissingPoints($baseItem);
        $attributes = $baseItem->attributes ?? [];
        $level = max(1, (int) ($attributes['needLevel'] ?? 1));

        if ($baseItem->category === null || $baseItem->rarity === null) {
            throw ValidationException::withMessages(['reverse' => 'Uzupełnij kategorię i rzadkość przedmiotu przed przeliczeniem.']);
        }

        try {
            $reverse = (array) $this->apiService->getReverseScaleAttributes(
                $level, $baseItem->category->value, $attributes['needProfessions'] ?? [],
                $baseItem->rarity, $attributes,
            );

            Validator::make($reverse, [
                'attributePoints' => ['present', 'array:'.implode(',', self::ATTRIBUTE_POINTS)],
                'attributePoints.*' => ['integer'],
                'manualAttributePoints' => ['present', 'array:counter,pierce,pierceBlock,deepWoundChance'],
                'manualAttributePoints.*' => ['integer'],
                'attackElements' => ['present', 'array'],
                'attackElements.*' => ['in:PHYSICAL,FIRE,FROST,LIGHT,POISON'],
            ])->validate();

            $scaled = (array) $this->apiService->getScaleAttributes([
                ...$reverse['attributePoints'],
                ...$reverse['manualAttributePoints'],
                'lvl' => $level,
                'itemCategory' => $baseItem->category->value,
                'itemProfessions' => implode(',', $attributes['needProfessions'] ?? []),
                'rarity' => $baseItem->rarity,
                'attackElements' => implode(',', $reverse['attackElements']),
            ]);

            if (array_diff(array_keys($scaled), [...self::SCALED_ATTRIBUTES, 'needLevel']) !== []) {
                throw new \UnexpectedValueException('Invalid scaling response.');
            }

            foreach ($scaled as $attribute => $value) {
                $isRange = in_array($attribute, ['physicalDamage', 'poisonDamage', 'frostDamage', 'deepWoundChance'], true);
                if ($isRange && (! is_array($value) || ! array_is_list($value) || count($value) !== 2)) {
                    throw new \UnexpectedValueException('Invalid scaled attribute range.');
                }

                $values = $isRange ? $value : [$value];
                foreach ($values as $number) {
                    if (! is_int($number) && ! is_float($number)) {
                        throw new \UnexpectedValueException('Invalid scaled attribute value.');
                    }
                }
            }

            if ($scaled === [] && ($reverse['attributePoints'] !== [] || $reverse['manualAttributePoints'] !== [])) {
                throw new \UnexpectedValueException('Missing scaled attributes.');
            }
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages(['reverse' => 'Silnik nie zwrócił poprawnego przeliczenia. Spróbuj ponownie.']);
        }

        $recalculated = array_replace(array_diff_key($attributes, array_flip(self::SCALED_ATTRIBUTES)), $scaled);
        $preview = [
            'original_attributes' => $attributes,
            'recalculated_attributes' => $recalculated,
            'attribute_points' => $reverse['attributePoints'],
            'manual_attribute_points' => $reverse['manualAttributePoints'],
            'attack_elements' => $reverse['attackElements'],
        ];
        $token = (string) Str::uuid();
        Cache::put($this->cacheKey($baseItem, $userId, $token), [
            'fingerprint' => $this->fingerprint($baseItem),
            'preview' => $preview,
        ], now()->addMinutes(15));

        return [...$preview, 'preview_token' => $token];
    }

    public function confirm(BaseItem $baseItem, int $userId, string $token): void
    {
        $key = $this->cacheKey($baseItem, $userId, $token);
        $stored = Cache::get($key);

        if (! is_array($stored)) {
            throw ValidationException::withMessages(['reverse' => 'Podgląd wygasł. Przelicz przedmiot ponownie.']);
        }

        $baseItem->getConnection()->transaction(function () use ($baseItem, $stored): void {
            $current = $baseItem->newQuery()->lockForUpdate()->findOrFail($baseItem->id);
            $this->ensureMissingPoints($current);

            if ($stored['fingerprint'] !== $this->fingerprint($current)) {
                throw ValidationException::withMessages(['reverse' => 'Przedmiot zmienił się od czasu podglądu. Przelicz go ponownie.']);
            }

            $preview = $stored['preview'];
            $current->update([
                'attributes' => (object) $preview['recalculated_attributes'],
                'attribute_points' => (object) $preview['attribute_points'],
                'manual_attribute_points' => (object) $preview['manual_attribute_points'],
                'reverse_attributes' => [
                    'attributePoints' => $preview['attribute_points'],
                    'manualAttributePoints' => $preview['manual_attribute_points'],
                    'attackElements' => $preview['attack_elements'],
                    'originalAttributes' => $preview['original_attributes'],
                    'approved_at' => now()->toIso8601String(),
                ],
                'edited_manually' => true,
            ]);
        });

        Cache::forget($key);
    }

    private function ensureMissingPoints(BaseItem $baseItem): void
    {
        if (! $baseItem->hasMissingAttributePoints()) {
            throw ValidationException::withMessages(['reverse' => 'Przedmiot ma już obliczone punkty atrybutów.']);
        }
    }

    private function cacheKey(BaseItem $baseItem, int $userId, string $token): string
    {
        return 'base-item-reverse:'.hash('sha256', implode(':', [$baseItem->getConnectionName(), $baseItem->id, $userId, $token]));
    }

    private function fingerprint(BaseItem $baseItem): string
    {
        return hash('sha256', json_encode([
            $baseItem->attributes, $baseItem->category, $baseItem->rarity,
            $baseItem->attribute_points, $baseItem->manual_attribute_points, $baseItem->reverse_attributes,
        ], JSON_THROW_ON_ERROR));
    }
}

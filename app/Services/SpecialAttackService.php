<?php

namespace App\Services;

use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use App\Http\Resources\SpecialAttackResource;
use App\Models\SpecialAttack;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class SpecialAttackService extends BaseService
{
    public function __construct(private readonly SpecialAttack $specialAttackModel) {}

    /**
     * @throws \Exception
     */
    public function getAll(): AnonymousResourceCollection
    {
        return $this->fetchData(
            SpecialAttackResource::class,
            $this->specialAttackModel,
            new TableService(
                columns: [
                    'id' => new TableTextColumn(sortable: true),
                    'name' => new TableTextColumn(sortable: true),
                    'attack_type' => new TableDropdownColumn(
                        placeholder: 'Typ ataku',
                        options: array_map(function ($type) {
                            return new TableDropdownOption($type->description(), fn ($query) => $query->where('attack_type', $type->value));
                        }, SpecialAttackType::cases())
                    ),
                    'charge_turns' => new TableTextColumn(
                        placeholder: 'Tur ładowania',
                        sortable: true
                    ),
                    'target' => new TableDropdownColumn(
                        placeholder: 'Cel',
                        options: array_map(function ($target) {
                            return new TableDropdownOption($target->description(), fn ($query) => $query->where('target', $target->value));
                        }, SpecialAttackTarget::cases())
                    ),
                    'base_npcs_count' => new TableTextColumn(
                        placeholder: 'Ilość Base NPC',
                        sortable: true,
                        sortPath: 'base_npcs_count'
                    ),
                ],
                globalFilterColumns: ['name', 'attack_type', 'target'],
            )
        );
    }

    public function store(array $validated): SpecialAttack
    {
        $specialAttack = SpecialAttack::create($validated);

        if (isset($validated['effects'])) {
            $this->syncEffects($specialAttack, $validated['effects']);
        }

        if (isset($validated['damages'])) {
            $this->syncDamages($specialAttack, $validated['damages']);
        }

        return $specialAttack;
    }

    public function destroy(SpecialAttack $specialAttack)
    {
        $specialAttack->delete();
    }

    public function update(SpecialAttack $specialAttack, mixed $validated): void
    {
        $specialAttack->update($validated);

        if (isset($validated['effects'])) {
            $this->syncEffects($specialAttack, $validated['effects']);
        }

        if (isset($validated['damages'])) {
            $this->syncDamages($specialAttack, $validated['damages']);
        }
    }

    public function search(string $search)
    {
        return SpecialAttackResource::collection(
            $this->specialAttackModel
                ->where('name', 'like', '%'.$search.'%')
                ->with(['effects', 'damages'])
                ->limit(25)
                ->get()
        );
    }

    private function syncEffects(SpecialAttack $specialAttack, array $effects): void
    {
        $specialAttack->effects()->delete();

        foreach ($effects as $effect) {
            if (! empty($effect['type']) && isset($effect['value']) && isset($effect['duration'])) {
                $specialAttack->effects()->create([
                    'type' => $effect['type'],
                    'value' => (float) $effect['value'],
                    'duration' => (int) $effect['duration'],
                ]);
            }
        }
    }

    private function syncDamages(SpecialAttack $specialAttack, array $damages): void
    {
        $specialAttack->damages()->delete();

        foreach ($damages as $damage) {
            if (! empty($damage['element']) && isset($damage['min_damage']) && isset($damage['max_damage'])) {
                $specialAttack->damages()->create([
                    'element' => $damage['element'],
                    'min_damage' => (int) $damage['min_damage'],
                    'max_damage' => (int) $damage['max_damage'],
                ]);
            }
        }
    }
}

<?php

namespace App\Services;

use App\Enums\SpecialAttackTarget;
use App\Enums\SpecialAttackType;
use App\Http\Resources\SpecialAttackResource;
use App\Models\SpecialAttack;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class SpecialAttackService extends BaseService
{
    public function __construct(private readonly SpecialAttack $specialAttackModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
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
                            return new TableDropdownOption($type->description(), fn($query) => $query->where('attack_type', $type->value));
                        }, SpecialAttackType::cases())
                    ),
                    'charge_turns' => new TableTextColumn(
                        placeholder: 'Tur ładowania',
                        sortable: true
                    ),
                    'target' => new TableDropdownColumn(
                        placeholder: 'Cel',
                        options: array_map(function ($target) {
                            return new TableDropdownOption($target->description(), fn($query) => $query->where('target', $target->value));
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
        return SpecialAttack::create($validated);
    }

    public function destroy(SpecialAttack $specialAttack)
    {
        $specialAttack->delete();
    }

    public function update(SpecialAttack $specialAttack, mixed $validated)
    {
        $specialAttack->update($validated);
    }

    public function search(string $search)
    {
        return \App\Http\Resources\SpecialAttackResource::collection(
            $this->specialAttackModel
                ->where('name', 'like', '%' . $search . '%')
                ->with(['effects', 'damages'])
                ->limit(25)
                ->get()
        );
    }
}

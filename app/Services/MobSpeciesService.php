<?php

namespace App\Services;

use App\Http\Resources\MobSpeciesListResource;
use App\Models\MobSpecies;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

class MobSpeciesService extends BaseService
{
    public function __construct(
        private readonly MobSpecies $mobSpeciesModel,
    ) {}

    public function getAll()
    {
        return $this->fetchData(
            MobSpeciesListResource::class,
            $this->mobSpeciesModel->newQuery()->with('baseNpcs'),
            new TableService(
                columns: [
                    'id' => new TableTextColumn(sortable: true),
                    'name' => new TableTextColumn(sortable: true),
                ],
                globalFilterColumns: ['name']
            )
        );
    }

    public function attachBaseNpc(MobSpecies $mobSpecies, int $baseNpcId): void
    {
        $mobSpecies->baseNpcs()->syncWithoutDetaching([$baseNpcId]);
    }

    public function detachBaseNpc(MobSpecies $mobSpecies, int $baseNpcId): void
    {
        $mobSpecies->baseNpcs()->detach([$baseNpcId]);
    }
}


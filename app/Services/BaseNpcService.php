<?php
namespace App\Services;

use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\PureNpcWithOnlyLocationsResource;
use App\Models\BaseNpc;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class BaseNpcService extends BaseService
{
    public function __construct(private readonly BaseNpc $baseNpcModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            BaseNpcResource::class,
            $this->baseNpcModel,
            new TableService(
                columns: [
                  'name' => new TableTextColumn(sortable: true),
                  'src' => new TableTextColumn(sortable: true),
                  'lvl' => new TableTextColumn(sortable: true),
                ],
                globalFilterColumns: ['name'],
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function getLocations(BaseNpc $baseNpc): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            PureNpcWithOnlyLocationsResource::class,
            $baseNpc->locations(),
            new TableService(
                globalFilterColumns: [] //todo - szukanie po relacji. moja libka chyba tego nie obsluguje
            )
        );
    }

    public function search(string $search)
    {
        return BaseNpcResource::collection($this->baseNpcModel->where('name', 'like', '%' . $search . '%')->limit(25)->get());
    }

    public function store(array $validated): BaseNpc
    {
        return BaseNpc::create($validated);
    }

    public function destroy(BaseNpc $baseNpc)
    {
        $baseNpc->delete();
    }

    public function update(BaseNpc $baseNpc, mixed $validated)
    {
        $baseNpc->update($validated);
    }
}

<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Models\Map;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class MapService extends BaseService
{
    public function __construct(private readonly Map $mapModel)
    {
    }

    /**
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            MapResource::class,
            $this->mapModel,
            new TableService(
                globalFilterColumns: ['name']
            )
        );
    }

    public function search(string $search = '')
    {
        return $this->mapModel->where('name', 'like', '%' . $search . '%')->limit(10)->get();
    }
}

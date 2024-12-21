<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Models\Map;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

final class MapService extends BaseService
{
    public function __construct(private readonly Map $mapModel)
    {
    }

    public function getAll()
    {
        return $this->fetchData(
            MapResource::class,
            $this->mapModel->newQuery()
        );
    }
}

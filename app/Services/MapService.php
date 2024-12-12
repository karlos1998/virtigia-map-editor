<?php

namespace App\Services;

use App\Http\Resources\MapResource;
use App\Models\Map;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

final class MapService extends BaseService
{

    private Map $mapModel;
    public function __construct(Map $map)
    {
        $this->mapModel = $map->setConnectionName('retro');
    }

    public function getAll()
    {
        return $this->fetchData(
            MapResource::class,
            $this->mapModel->newQuery()
        );
//        dd($this->mapModel->newQuery()->get());
    }
}

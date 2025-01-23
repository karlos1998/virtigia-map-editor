<?php

namespace App\Services;

use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class BaseItemService extends BaseService
{
    public function __construct(private readonly BaseItem $baseItemModel)
    {
    }

    /**§
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            BaseItemResource::class,
            $this->baseItemModel,
            new TableService(
                globalFilterColumns: ['name', 'src'],
            )
        );
    }

    public function search(string $search = '')
    {
        return $this->baseItemModel->where('name', 'like', '%' . $search . '%')->limit(10)->get();
    }
}

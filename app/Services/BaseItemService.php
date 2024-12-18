<?php

namespace App\Services;

use App\Http\Resources\BaseItemResource;
use App\Models\BaseItem;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;

final class BaseItemService extends BaseService
{
    private BaseItem $baseItemModel;
    public function __construct(BaseItem $baseItem)
    {
        $this->baseItemModel = $baseItem->setConnectionName('retro');
    }

    /**§
     * @throws \Exception
     */
    public function getAll(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return $this->fetchData(
            BaseItemResource::class,
            $this->baseItemModel->newQuery()
        );
    }
}

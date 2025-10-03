<?php

namespace App\Services;

use App\Http\Resources\RenewableMapItemResource;
use App\Models\RenewableMapItem;
use App\Http\Resources\BaseItemResource;
use App\Http\Resources\MapResource;
use Karlos3098\LaravelPrimevueTableService\Services\BaseService;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableTextColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownColumn;
use Karlos3098\LaravelPrimevueTableService\Services\Columns\TableDropdownOptions\TableDropdownOption;
use Karlos3098\LaravelPrimevueTableService\Services\TableService;

final class RenewableMapItemService extends BaseService
{
    public function __construct(private readonly RenewableMapItem $model)
    {
    }

    public function getAll()
    {
        return $this->fetchData(
            \App\Http\Resources\RenewableMapItemWithMapResource::class,
            $this->model->with(['baseItem', 'map']),
            new TableService(
                columns: [
                    'map' => new TableTextColumn(
                        placeholder: 'Mapa',
                        sortable: true,
                        queryPaths: ['map.name'],
                        sortPath: 'map_id',
                    ),
                    'item' => new TableTextColumn(
                        placeholder: 'Przedmiot',
                        sortable: true,
                        queryPaths: ['baseItem.name'],
                        sortPath: 'base_item_id'
                    ),
                    'x' => new TableTextColumn(placeholder: 'X', sortable: true),
                    'y' => new TableTextColumn(placeholder: 'Y', sortable: true),
                    'respawn_time_seconds' => new TableTextColumn(placeholder: 'Respawn (s)', sortable: true),
                ],
                globalFilterColumns: ['x', 'y'],
                rowsPerPage: [100, 300, 500]
            )
        );
    }
}

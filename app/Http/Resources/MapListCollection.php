<?php

namespace App\Http\Resources;

use App\OpenApi\PaginationMeta;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: MapListCollection::class,
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: MapListResource::class)
        ),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ],
    type: 'object'
)]
class MapListCollection extends ApiPaginatedResourceCollection
{
    public $collects = MapListResource::class;
}

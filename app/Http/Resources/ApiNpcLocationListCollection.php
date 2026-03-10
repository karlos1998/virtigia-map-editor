<?php

namespace App\Http\Resources;

use App\OpenApi\PaginationMeta;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiNpcLocationListCollection::class,
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: ApiNpcLocationResource::class)
        ),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ]
)]
class ApiNpcLocationListCollection extends ApiPaginatedResourceCollection
{
    public $collects = ApiNpcLocationResource::class;
}

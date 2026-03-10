<?php

namespace App\Http\Resources;

use App\OpenApi\PaginationMeta;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiNpcListCollection::class,
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: ApiNpcListResource::class)
        ),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ]
)]
class ApiNpcListCollection extends ApiPaginatedResourceCollection
{
    public $collects = ApiNpcListResource::class;
}

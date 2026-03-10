<?php

namespace App\Http\Resources;

use App\OpenApi\PaginationMeta;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiBaseNpcListCollection::class,
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: ApiBaseNpcListResource::class)
        ),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ]
)]
class ApiBaseNpcListCollection extends ApiPaginatedResourceCollection
{
    public $collects = ApiBaseNpcListResource::class;
}

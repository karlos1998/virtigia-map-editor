<?php

namespace App\Http\Resources;

use App\OpenApi\PaginationMeta;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiQuestListCollection::class,
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: ApiQuestListResource::class)
        ),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ]
)]
class ApiQuestListCollection extends ApiPaginatedResourceCollection
{
    public $collects = ApiQuestListResource::class;
}

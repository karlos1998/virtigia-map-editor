<?php

namespace App\Http\Resources;

use App\OpenApi\PaginationMeta;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: ApiDialogListCollection::class,
    type: 'object',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: ApiDialogListResource::class)
        ),
        new OA\Property(property: 'meta', ref: PaginationMeta::class),
    ]
)]
class ApiDialogListCollection extends ApiPaginatedResourceCollection
{
    public $collects = ApiDialogListResource::class;
}

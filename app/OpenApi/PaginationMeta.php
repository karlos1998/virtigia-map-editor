<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: PaginationMeta::class,
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'last_page', type: 'integer'),
        new OA\Property(property: 'per_page', type: 'integer'),
        new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'from', type: 'integer', nullable: true),
        new OA\Property(property: 'to', type: 'integer', nullable: true),
        new OA\Property(property: 'world', type: 'string'),
    ]
)]
class PaginationMeta {}

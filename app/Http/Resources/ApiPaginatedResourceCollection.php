<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class ApiPaginatedResourceCollection extends ResourceCollection
{
    /**
     * @param  array<string, mixed>  $paginated
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function paginationInformation(Request $request, array $paginated, array $default): array
    {
        return [
            'meta' => [
                'current_page' => $paginated['current_page'] ?? 1,
                'last_page' => $paginated['last_page'] ?? 1,
                'per_page' => $paginated['per_page'] ?? 0,
                'total' => $paginated['total'] ?? 0,
                'from' => $paginated['from'] ?? null,
                'to' => $paginated['to'] ?? null,
                'world' => $request->attributes->get('world'),
            ],
        ];
    }
}

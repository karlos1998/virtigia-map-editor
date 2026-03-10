<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\BaseNpcIndexRequest;
use App\Http\Resources\ApiBaseNpcDetailResource;
use App\Http\Resources\ApiBaseNpcListCollection;
use App\Models\BaseNpc;
use OpenApi\Attributes as OA;

class BaseNpcController extends Controller
{
    #[OA\Get(
        path: '/api/v1/base-npcs',
        operationId: 'getBaseNpcsList',
        summary: 'Pobranie listy bazowych NPC',
        tags: ['Base NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginowana lista bazowych NPC',
                content: new OA\JsonContent(ref: ApiBaseNpcListCollection::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function index(BaseNpcIndexRequest $request): ApiBaseNpcListCollection
    {
        $baseNpcs = BaseNpc::query()
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        return new ApiBaseNpcListCollection($baseNpcs);
    }

    #[OA\Get(
        path: '/api/v1/base-npcs/{baseNpcId}',
        operationId: 'getBaseNpcById',
        summary: 'Pobranie pojedynczego bazowego NPC',
        tags: ['Base NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'baseNpcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Szczegóły bazowego NPC',
                content: new OA\JsonContent(ref: ApiBaseNpcDetailResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'Bazowy NPC nie został znaleziony'),
        ],
    )]
    public function show(int $baseNpcId): ApiBaseNpcDetailResource
    {
        $baseNpc = BaseNpc::query()->findOrFail($baseNpcId);

        return new ApiBaseNpcDetailResource($baseNpc);
    }
}

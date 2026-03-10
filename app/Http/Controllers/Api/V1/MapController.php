<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MapIndexRequest;
use App\Http\Resources\MapDetailResource;
use App\Http\Resources\MapListCollection;
use App\Models\Map;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class MapController extends Controller
{
    #[OA\Get(
        path: '/api/v1/maps',
        operationId: 'getMapsList',
        summary: 'Pobranie listy map (bez ciężkiego pola col)',
        tags: ['Maps'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Liczba rekordów na stronę (1-100)',
                schema: new OA\Schema(type: 'integer', default: 50)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginowana lista map',
                content: new OA\JsonContent(ref: MapListCollection::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function index(MapIndexRequest $request): MapListCollection
    {
        $maps = Map::query()
            ->select([
                'id',
                'name',
                'x',
                'y',
                'src',
                'thumbnail_src',
                'pvp',
                'battleground',
                'battleground2',
                'is_teleport_locked',
                'respawn_point_id',
            ])
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        return new MapListCollection($maps);
    }

    #[OA\Get(
        path: '/api/v1/maps/{mapId}',
        operationId: 'getMapById',
        summary: 'Pobranie pojedynczej mapy',
        tags: ['Maps'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'mapId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Szczegóły mapy',
                content: new OA\JsonContent(ref: MapDetailResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'Mapa nie została znaleziona'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function show(Request $request, int $mapId): MapDetailResource
    {
        $map = Map::query()->findOrFail($mapId);

        return new MapDetailResource($map);
    }
}

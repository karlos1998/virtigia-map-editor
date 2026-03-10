<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\NpcIndexRequest;
use App\Http\Requests\Api\NpcLocationIndexRequest;
use App\Http\Requests\Api\StoreApiNpcLocationRequest;
use App\Http\Requests\Api\StoreApiNpcRequest;
use App\Http\Requests\Api\UpdateApiNpcRequest;
use App\Http\Resources\ApiNpcDetailResource;
use App\Http\Resources\ApiNpcListCollection;
use App\Http\Resources\ApiNpcLocationListCollection;
use App\Http\Resources\ApiNpcLocationResource;
use App\Models\Npc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class NpcController extends Controller
{
    #[OA\Get(
        path: '/api/v1/npcs',
        operationId: 'getNpcsList',
        summary: 'Pobranie listy NPC',
        tags: ['NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 50)
            ),
            new OA\Parameter(
                name: 'base_npc_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginowana lista NPC',
                content: new OA\JsonContent(ref: ApiNpcListCollection::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function index(NpcIndexRequest $request): ApiNpcListCollection
    {
        $npcsQuery = Npc::query()
            ->with(['base'])
            ->withCount('locations')
            ->orderBy('id');

        $baseNpcId = $request->integer('base_npc_id');

        if ($baseNpcId > 0) {
            $npcsQuery->where('base_npc_id', $baseNpcId);
        }

        return new ApiNpcListCollection($npcsQuery->paginate($request->integer('per_page', 50)));
    }

    #[OA\Get(
        path: '/api/v1/npcs/{npcId}',
        operationId: 'getNpcById',
        summary: 'Pobranie pojedynczego NPC',
        tags: ['NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'npcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Szczegóły NPC',
                content: new OA\JsonContent(ref: ApiNpcDetailResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'NPC nie został znaleziony'),
        ],
    )]
    public function show(int $npcId): ApiNpcDetailResource
    {
        $npc = Npc::query()
            ->with(['base', 'locations.map'])
            ->findOrFail($npcId);

        return new ApiNpcDetailResource($npc);
    }

    #[OA\Post(
        path: '/api/v1/npcs',
        operationId: 'storeNpc',
        summary: 'Dodanie nowego NPC z pierwszą lokalizacją',
        tags: ['NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['base_npc_id', 'map_id', 'x', 'y'],
                properties: [
                    new OA\Property(property: 'base_npc_id', type: 'integer'),
                    new OA\Property(property: 'map_id', type: 'integer'),
                    new OA\Property(property: 'x', type: 'integer'),
                    new OA\Property(property: 'y', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'NPC został dodany',
                content: new OA\JsonContent(ref: ApiNpcDetailResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function store(StoreApiNpcRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $npc = new Npc;
        $npc->base()->associate($validated['base_npc_id']);
        $npc->save();

        $npc->locations()->create([
            'map_id' => $validated['map_id'],
            'x' => $validated['x'],
            'y' => $validated['y'],
        ]);

        $npc->load(['base', 'locations.map']);

        return (new ApiNpcDetailResource($npc))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/v1/npcs/{npcId}',
        operationId: 'updateNpc',
        summary: 'Edycja NPC (name, lvl, rank na powiązanym base NPC)',
        tags: ['NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'npcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'lvl', type: 'integer'),
                    new OA\Property(property: 'rank', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'NPC został zaktualizowany',
                content: new OA\JsonContent(ref: ApiNpcDetailResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'NPC nie został znaleziony'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function update(UpdateApiNpcRequest $request, int $npcId): ApiNpcDetailResource
    {
        $npc = Npc::query()->with('base')->findOrFail($npcId);

        $npc->base->update($request->validated());

        $npc->refresh()->load(['base', 'locations.map']);

        return new ApiNpcDetailResource($npc);
    }

    #[OA\Delete(
        path: '/api/v1/npcs/{npcId}',
        operationId: 'destroyNpc',
        summary: 'Usunięcie NPC',
        tags: ['NPC'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'npcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'NPC został usunięty'),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'NPC nie został znaleziony'),
        ],
    )]
    public function destroy(int $npcId): Response
    {
        $npc = Npc::query()->findOrFail($npcId);
        $npc->locations()->delete();
        $npc->delete();

        return response()->noContent();
    }

    #[OA\Get(
        path: '/api/v1/npcs/{npcId}/locations',
        operationId: 'getNpcLocationsList',
        summary: 'Pobranie paginowanej listy lokalizacji NPC',
        tags: ['NPC Locations'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'npcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
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
                description: 'Paginowana lista lokalizacji NPC',
                content: new OA\JsonContent(ref: ApiNpcLocationListCollection::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'NPC nie został znaleziony'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function indexLocations(NpcLocationIndexRequest $request, int $npcId): ApiNpcLocationListCollection
    {
        $npc = Npc::query()->findOrFail($npcId);

        $locations = $npc->locations()
            ->with('map')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 50));

        return new ApiNpcLocationListCollection($locations);
    }

    #[OA\Post(
        path: '/api/v1/npcs/{npcId}/locations',
        operationId: 'storeNpcLocation',
        summary: 'Dodanie nowej lokalizacji NPC',
        tags: ['NPC Locations'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'npcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['map_id', 'x', 'y'],
                properties: [
                    new OA\Property(property: 'map_id', type: 'integer'),
                    new OA\Property(property: 'x', type: 'integer'),
                    new OA\Property(property: 'y', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Lokalizacja została dodana',
                content: new OA\JsonContent(ref: ApiNpcLocationResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'NPC nie został znaleziony'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function storeLocation(StoreApiNpcLocationRequest $request, int $npcId): JsonResponse
    {
        $npc = Npc::query()->findOrFail($npcId);

        $npcLocation = $npc->locations()->create($request->validated());
        $npcLocation->load('map');

        return (new ApiNpcLocationResource($npcLocation))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Delete(
        path: '/api/v1/npcs/{npcId}/locations/{locationId}',
        operationId: 'destroyNpcLocation',
        summary: 'Usunięcie lokalizacji NPC',
        tags: ['NPC Locations'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(
                name: 'npcId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'locationId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Lokalizacja została usunięta'),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'NPC lub lokalizacja nie zostały znalezione'),
        ],
    )]
    public function destroyLocation(int $npcId, int $locationId): Response
    {
        $npc = Npc::query()->findOrFail($npcId);
        $npcLocation = $npc->locations()->findOrFail($locationId);

        $npcLocation->delete();

        return response()->noContent();
    }
}

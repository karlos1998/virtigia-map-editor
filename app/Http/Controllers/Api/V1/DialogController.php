<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DialogIndexRequest;
use App\Http\Resources\ApiDialogDetailResource;
use App\Http\Resources\ApiDialogListCollection;
use App\Models\Dialog;
use OpenApi\Attributes as OA;

class DialogController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dialogs',
        operationId: 'getDialogsList',
        summary: 'Pobranie listy dialogów',
        tags: ['Dialogs'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'npc_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'npc_base_npc_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'node_type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'has_npcs', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'has_nodes', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'has_edges', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'updated_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'updated_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'created_at', 'updated_at', 'npcs_count', 'nodes_count', 'edges_count'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginowana lista dialogów',
                content: new OA\JsonContent(ref: ApiDialogListCollection::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function index(DialogIndexRequest $request): ApiDialogListCollection
    {
        $query = Dialog::query()
            ->withCount(['npcs', 'nodes', 'edges']);

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        if ($request->filled('npc_id')) {
            $npcId = $request->integer('npc_id');
            $query->whereHas('npcs', fn ($builder) => $builder->where('id', $npcId));
        }

        if ($request->filled('npc_base_npc_id')) {
            $baseNpcId = $request->integer('npc_base_npc_id');
            $query->whereHas('npcs', fn ($builder) => $builder->where('base_npc_id', $baseNpcId));
        }

        if ($request->filled('node_type')) {
            $query->whereHas('nodes', fn ($builder) => $builder->where('type', $request->string('node_type')));
        }

        if ($request->has('has_npcs')) {
            if ($request->boolean('has_npcs')) {
                $query->has('npcs');
            } else {
                $query->doesntHave('npcs');
            }
        }

        if ($request->has('has_nodes')) {
            if ($request->boolean('has_nodes')) {
                $query->has('nodes');
            } else {
                $query->doesntHave('nodes');
            }
        }

        if ($request->has('has_edges')) {
            if ($request->boolean('has_edges')) {
                $query->has('edges');
            } else {
                $query->doesntHave('edges');
            }
        }

        if ($request->filled('updated_from')) {
            $query->whereDate('updated_at', '>=', $request->string('updated_from'));
        }

        if ($request->filled('updated_to')) {
            $query->whereDate('updated_at', '<=', $request->string('updated_to'));
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');

        $query->orderBy($sort, $direction);

        return new ApiDialogListCollection($query->paginate($request->integer('per_page', 50)));
    }

    #[OA\Get(
        path: '/api/v1/dialogs/{dialogId}',
        operationId: 'getDialogById',
        summary: 'Pobranie pojedynczego dialogu razem z grafem (nodes/options/edges)',
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        tags: ['Dialogs'],
        parameters: [
            new OA\Parameter(
                name: 'dialogId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Szczegóły dialogu',
                content: new OA\JsonContent(ref: ApiDialogDetailResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 404, description: 'Dialog nie został znaleziony'),
        ],
    )]
    public function show(int $dialogId): ApiDialogDetailResource
    {
        $dialog = Dialog::query()
            ->with([
                'npcs.base',
                'npcs.locations',
                'nodes' => fn ($query) => $query->with([
                    'shop',
                    'hotel',
                    'options' => fn ($optionQuery) => $optionQuery->with([
                        'edges.targetNode',
                    ]),
                ]),
                'edges.targetNode',
            ])
            ->findOrFail($dialogId);

        return new ApiDialogDetailResource($dialog);
    }
}

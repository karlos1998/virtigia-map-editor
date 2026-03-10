<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuestIndexRequest;
use App\Http\Resources\ApiQuestListCollection;
use App\Models\Quest;
use OpenApi\Attributes as OA;

class QuestController extends Controller
{
    #[OA\Get(
        path: '/api/v1/quests',
        operationId: 'getQuestsList',
        summary: 'Pobranie listy questów',
        tags: ['Quests'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_daily', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'has_steps', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50)),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'created_at', 'updated_at', 'steps_count'])),
            new OA\Parameter(name: 'direction', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginowana lista questów',
                content: new OA\JsonContent(ref: ApiQuestListCollection::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
            new OA\Response(response: 422, description: 'Błąd walidacji'),
        ],
    )]
    public function index(QuestIndexRequest $request): ApiQuestListCollection
    {
        $query = Quest::query()
            ->with('steps')
            ->withCount('steps');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        if ($request->has('is_daily')) {
            if ($request->boolean('is_daily')) {
                $query->whereHas('steps', fn ($builder) => $builder->where('auto_advance_next_day', true));
            } else {
                $query->whereDoesntHave('steps', fn ($builder) => $builder->where('auto_advance_next_day', true));
            }
        }

        if ($request->has('has_steps')) {
            if ($request->boolean('has_steps')) {
                $query->has('steps');
            } else {
                $query->doesntHave('steps');
            }
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'asc');

        $query->orderBy($sort, $direction);

        return new ApiQuestListCollection($query->paginate($request->integer('per_page', 50)));
    }
}

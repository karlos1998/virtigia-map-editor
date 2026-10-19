<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorldTemplateUpdateChangesRequest;
use App\Http\Requests\WorldTemplateUpdateRequest;
use App\Models\WorldTemplate;
use App\Models\WorldTemplateUpdate;
use App\Services\WorldTemplateUpdateService;
use Illuminate\Http\JsonResponse;

class WorldTemplateUpdateController extends Controller
{
    public function __construct(private readonly WorldTemplateUpdateService $updates) {}

    public function store(WorldTemplateUpdateRequest $request, WorldTemplate $template): JsonResponse
    {
        return response()->json($this->updates->start($template, (int) $request->user()->id)->publicStatus(), 202);
    }

    public function show(WorldTemplateUpdate $update): JsonResponse
    {
        return response()->json($update->publicStatus());
    }

    public function approve(WorldTemplateUpdateRequest $request, WorldTemplateUpdate $update): JsonResponse
    {
        return response()->json($this->updates->approve($update)->publicStatus(), 202);
    }

    public function changes(WorldTemplateUpdateChangesRequest $request, WorldTemplateUpdate $update): JsonResponse
    {
        abort_if($update->snapshot_pruned, 410, 'Migawka wygasła. Przygotuj nowe porównanie.');
        $filters = $request->validated();
        $query = $update->snapshotRows()->whereNotNull('change_type');
        if (! empty($filters['table'])) {
            $query->where('table_name', $filters['table']);
        }
        if (! empty($filters['type'])) {
            $query->where('change_type', $filters['type']);
        }
        if (isset($filters['search']) && $filters['search'] !== '') {
            $search = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['search']);
            $query->whereRaw("row_label LIKE ? ESCAPE '!'", ['%'.$search.'%']);
        }

        return response()->json($query->select(['id', 'table_name', 'row_label', 'change_type', 'changed_columns'])->orderBy('id')->paginate(30));
    }

    public function detail(WorldTemplateUpdate $update, int $row): JsonResponse
    {
        abort_if($update->snapshot_pruned, 410);
        $change = $update->snapshotRows()->where('id', $row)->whereNotNull('change_type')->first();
        abort_if($change === null, 404);

        return response()->json([
            'id' => $change->id,
            'before' => $change->before_data === null ? null : json_decode($change->before_data, true, flags: JSON_THROW_ON_ERROR),
            'after' => $change->after_data === null ? null : json_decode($change->after_data, true, flags: JSON_THROW_ON_ERROR),
            'changed_columns' => json_decode($change->changed_columns, true, flags: JSON_THROW_ON_ERROR),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\BaseItemCategory;
use App\Enums\BaseItemRarity;
use App\Http\Requests\IndexBaseItemDuplicateViewRequest;
use App\Http\Resources\BaseItemDuplicateViewResource;
use App\Services\BaseItemDuplicateViewService;
use Inertia\Inertia;
use Inertia\Response;

class BaseItemDuplicateViewController extends Controller
{
    public function __construct(private readonly BaseItemDuplicateViewService $baseItemDuplicateViewService) {}

    public function index(IndexBaseItemDuplicateViewRequest $request): Response
    {
        $filters = $request->filters();
        $perPage = $request->perPage();

        return Inertia::render('BaseItem/Duplicates', [
            'duplicates' => BaseItemDuplicateViewResource::collection(
                $this->baseItemDuplicateViewService->paginate($filters, $perPage)
            ),
            'filters' => $filters,
            'perPage' => $perPage,
            'categoryOptions' => BaseItemCategory::toDropdownList(),
            'rarityOptions' => BaseItemRarity::toDropdownList(),
        ]);
    }
}

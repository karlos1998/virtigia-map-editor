<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRenewableMapItemRequest;
use App\Models\RenewableMapItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RenewableMapItemController extends Controller
{
    public function __construct(
        private readonly \App\Services\RenewableMapItemService $renewableMapItemService,
    )
    {
    }


    public function store(StoreRenewableMapItemRequest $request): void
    {
        RenewableMapItem::create($request->validated());
    }

    public function destroy($mapId, $renewableItemId): void
    {
        \App\Models\RenewableMapItem::where('map_id', $mapId)->where('id', $renewableItemId)->delete();
    }

    public function index(): \Inertia\Response
    {
        return \Inertia\Inertia::render('RenewableMapItem/Index', [
            'items' => $this->renewableMapItemService->getAll(),
        ]);
    }
}

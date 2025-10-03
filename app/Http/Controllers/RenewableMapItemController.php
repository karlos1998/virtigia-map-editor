<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRenewableMapItemRequest;
use App\Models\RenewableMapItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RenewableMapItemController extends Controller
{
    public function store(StoreRenewableMapItemRequest $request): void
    {
        RenewableMapItem::create($request->validated());
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\BaseItemService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BaseItemController extends Controller
{


    public function __construct(private readonly BaseItemService $baseItemService)
    {
    }

    public function index()
    {
        return Inertia::render('BaseItem/Index', [
            'items' => $this->baseItemService->getAll(),
        ]);
    }

    public function search(Request $request)
    {
        return response()->json($this->baseItemService->search($request->get('query', '')));
    }
}

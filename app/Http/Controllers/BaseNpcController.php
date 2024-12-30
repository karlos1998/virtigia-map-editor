<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBaseNpcRequest;
use App\Http\Requests\UpdateBaseNpcRequest;
use App\Http\Resources\BaseNpcResource;
use App\Http\Resources\NpcLocationResource;
use App\Http\Resources\PureNpcWithOnlyLocationsResource;
use App\Models\BaseNpc;
use App\Services\BaseNpcService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BaseNpcController extends Controller
{
    public function __construct(private readonly BaseNpcService $baseNpcService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('BaseNpc/Index', [
            'baseNpcs' => $this->baseNpcService->getAll(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        dd(count(Storage::disk('s3')->files('img')));
        return Inertia::render('BaseNpc/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBaseNpcRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     * @throws \Exception
     */
    public function show(BaseNpc $baseNpc)
    {
        return Inertia::render('BaseNpc/Show', [
            'baseNpc' => BaseNpcResource::make($baseNpc),
            'locations' => $this->baseNpcService->getLocations($baseNpc),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BaseNpc $baseNpc)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBaseNpcRequest $request, BaseNpc $baseNpc)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BaseNpc $baseNpc)
    {
        //
    }

    public function search(Request $request)
    {
        return response()->json($this->baseNpcService->search($request->get('query', '')));
    }
}

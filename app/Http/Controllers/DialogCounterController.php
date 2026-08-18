<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDialogCounterRequest;
use App\Http\Requests\UpdateDialogCounterRequest;
use App\Models\DialogCounter;
use Inertia\Inertia;

class DialogCounterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Inertia::render('DialogCounter/Index', [
            'dialogCounters' => DialogCounter::all(['id', 'name', 'scope']),
        ]);
    }

    /**
     * Return JSON list for API usage (dropdowns).
     */
    public function indexJson()
    {
        return response()->json(DialogCounter::all(['id', 'name', 'scope']));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('DialogCounter/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDialogCounterRequest $request)
    {
        DialogCounter::create($request->validated());

        return to_route('dialog-counters.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DialogCounter $dialogCounter)
    {
        return Inertia::render('DialogCounter/Edit', [
            'dialogCounter' => $dialogCounter,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDialogCounterRequest $request, DialogCounter $dialogCounter)
    {
        $dialogCounter->update($request->validated());

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DialogCounter $dialogCounter)
    {
        $dialogCounter->delete();

        return to_route('dialog-counters.index');
    }
}

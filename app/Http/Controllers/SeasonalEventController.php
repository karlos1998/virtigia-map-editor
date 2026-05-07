<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSeasonalEventRequest;
use App\Http\Requests\UpdateSeasonalEventRequest;
use App\Models\SeasonalEvent;
use App\Services\SeasonalEventService;
use Inertia\Inertia;

class SeasonalEventController extends Controller
{
    public function __construct(private readonly SeasonalEventService $seasonalEventService) {}

    public function index()
    {
        return Inertia::render('SeasonalEvent/Index', [
            'events' => $this->seasonalEventService->getAllForList(),
        ]);
    }

    public function indexJson()
    {
        return response()->json($this->seasonalEventService->getAllForList());
    }

    public function show(SeasonalEvent $seasonalEvent)
    {
        return Inertia::render('SeasonalEvent/Show', $this->seasonalEventService->getShowData($seasonalEvent));
    }

    public function store(StoreSeasonalEventRequest $request)
    {
        $this->seasonalEventService->create($request->validated());

        return back();
    }

    public function update(UpdateSeasonalEventRequest $request, SeasonalEvent $seasonalEvent)
    {
        $this->seasonalEventService->update($seasonalEvent, $request->validated());

        return back();
    }

    public function destroy(SeasonalEvent $seasonalEvent)
    {
        $this->seasonalEventService->delete($seasonalEvent);

        return back();
    }
}

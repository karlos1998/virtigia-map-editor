<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapTrackRequest;
use App\Http\Requests\UpdateMapTrackRequest;
use App\Http\Resources\MapTrackResource;
use App\Models\DialogCounter;
use App\Models\MapTrack;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MapTrackController extends Controller
{
    public function index(): Response
    {
        $tracks = MapTrack::query()
            ->with('dialogCounter')
            ->withCount('checkpoints')
            ->orderBy('name')
            ->get();

        return Inertia::render('MapTrack/Index', [
            'tracks' => MapTrackResource::collection($tracks),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('MapTrack/Create', [
            'dialogCounters' => $this->dialogCounters(),
        ]);
    }

    public function store(StoreMapTrackRequest $request): RedirectResponse
    {
        $track = MapTrack::query()->create($request->validated());

        return to_route('map-tracks.edit', $track)->with('success', 'Trasa została utworzona.');
    }

    public function edit(MapTrack $mapTrack): Response
    {
        $mapTrack->load([
            'dialogCounter',
            'checkpoints.map',
            'checkpoints.tiles',
        ]);

        return Inertia::render('MapTrack/Edit', [
            'track' => MapTrackResource::make($mapTrack),
            'dialogCounters' => $this->dialogCounters(),
        ]);
    }

    public function update(UpdateMapTrackRequest $request, MapTrack $mapTrack): RedirectResponse
    {
        $mapTrack->update($request->validated());

        return back()->with('success', 'Trasa została zapisana.');
    }

    public function destroy(MapTrack $mapTrack): RedirectResponse
    {
        $mapTrack->delete();

        return to_route('map-tracks.index')->with('success', 'Trasa została usunięta.');
    }

    private function dialogCounters(): array
    {
        return DialogCounter::query()
            ->orderBy('name')
            ->get(['id', 'name', 'scope'])
            ->map(fn (DialogCounter $counter): array => [
                'id' => $counter->id,
                'name' => $counter->name,
                'scope' => $counter->scope,
            ])
            ->all();
    }
}

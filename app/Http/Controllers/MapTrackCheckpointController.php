<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMapTrackCheckpointRequest;
use App\Http\Requests\UpdateMapTrackCheckpointRequest;
use App\Models\MapTrack;
use App\Models\MapTrackCheckpoint;
use App\Services\MapTrackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MapTrackCheckpointController extends Controller
{
    public function __construct(private readonly MapTrackService $mapTrackService) {}

    public function store(StoreMapTrackCheckpointRequest $request, MapTrack $mapTrack): RedirectResponse
    {
        $this->mapTrackService->createCheckpoint($mapTrack, $request->validated());

        return back()->with('success', 'Checkpoint został dodany.');
    }

    public function update(
        UpdateMapTrackCheckpointRequest $request,
        MapTrack $mapTrack,
        MapTrackCheckpoint $checkpoint,
    ): RedirectResponse {
        $this->ensureCheckpointBelongsToTrack($mapTrack, $checkpoint);
        $this->mapTrackService->updateCheckpoint($checkpoint, $request->validated());

        return back()->with('success', 'Checkpoint został zapisany.');
    }

    public function destroy(MapTrack $mapTrack, MapTrackCheckpoint $checkpoint): RedirectResponse
    {
        $this->ensureCheckpointBelongsToTrack($mapTrack, $checkpoint);
        $this->mapTrackService->deleteCheckpoint($mapTrack, $checkpoint);

        return back()->with('success', 'Checkpoint został usunięty.');
    }

    public function move(Request $request, MapTrack $mapTrack, MapTrackCheckpoint $checkpoint): RedirectResponse
    {
        $this->ensureCheckpointBelongsToTrack($mapTrack, $checkpoint);
        $validated = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $this->mapTrackService->moveCheckpoint($mapTrack, $checkpoint, $validated['direction']);

        return back();
    }

    private function ensureCheckpointBelongsToTrack(MapTrack $track, MapTrackCheckpoint $checkpoint): void
    {
        abort_unless($checkpoint->map_track_id === $track->id, 404);
    }
}

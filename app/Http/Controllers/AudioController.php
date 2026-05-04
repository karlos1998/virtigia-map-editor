<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchAudioRequest;
use App\Http\Requests\StoreAudioRequest;
use App\Http\Requests\UpdateAudioRequest;
use App\Http\Resources\AudioResource;
use App\Http\Resources\BaseItemResource;
use App\Models\Audio;
use App\Services\AudioService;
use Inertia\Inertia;

class AudioController extends Controller
{
    public function __construct(
        private readonly AudioService $audioService,
    ) {}

    public function index()
    {
        return Inertia::render('Audio/Index', [
            'audios' => $this->audioService->getAll(),
        ]);
    }

    public function search(SearchAudioRequest $request)
    {
        $query = trim((string) $request->get('query', ''));

        if ($query === '') {
            return response()->json([]);
        }

        return response()->json(AudioResource::collection($this->audioService->searchByName($query, 30)));
    }

    public function show(Audio $audio)
    {
        return Inertia::render('Audio/Show', [
            'audio' => AudioResource::make($audio),
            'linkedItems' => BaseItemResource::collection($this->audioService->findItemsLinkedToAudio((int) $audio->id))->resolve(),
        ]);
    }

    public function fetch(Audio $audio)
    {
        return response()->json(AudioResource::make($audio)->resolve());
    }

    public function store(StoreAudioRequest $request)
    {
        $audio = $this->audioService->create($request->validated());

        return to_route('audios.show', $audio->id);
    }

    public function update(Audio $audio, UpdateAudioRequest $request)
    {
        $this->audioService->update($audio, $request->validated());

        return back();
    }
}

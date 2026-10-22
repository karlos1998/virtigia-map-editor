<?php

namespace App\Http\Controllers;

use App\Http\Requests\GeneratePlayingCardArtworkRequest;
use App\Services\PlayingCardArtworkService;
use Illuminate\Http\JsonResponse;

class PlayingCardArtworkController extends Controller
{
    public function __invoke(GeneratePlayingCardArtworkRequest $request, PlayingCardArtworkService $artwork): JsonResponse
    {
        return response()->json(['artwork' => $artwork->generate($request->validated('card'), $artwork->catalog())]);
    }
}

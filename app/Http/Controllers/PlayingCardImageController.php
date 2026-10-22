<?php

namespace App\Http\Controllers;

use App\Facades\AssetUrl;
use App\Http\Requests\UploadPlayingCardImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PlayingCardImageController extends Controller
{
    public function __invoke(UploadPlayingCardImageRequest $request): JsonResponse
    {
        $path = Storage::disk('s3')->putFile('img/playing-cards/uploads', $request->file('image'));
        if ($path === false) {
            throw new RuntimeException('Nie udało się zapisać ilustracji karty.');
        }

        return response()->json(['path' => $path, 'url' => AssetUrl::fromPath($path)]);
    }
}

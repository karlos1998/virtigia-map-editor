<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: '/api/v1/profile',
        operationId: 'getProfile',
        summary: 'Pobranie profilu aktualnego użytkownika',
        tags: ['Profile'],
        security: [['bearerAuth' => [], 'worldHeader' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil użytkownika',
                content: new OA\JsonContent(ref: UserProfileResource::class),
            ),
            new OA\Response(response: 401, description: 'Brak lub nieprawidłowy token'),
        ],
    )]
    public function show(Request $request): JsonResource
    {
        return new UserProfileResource($request->user());
    }
}

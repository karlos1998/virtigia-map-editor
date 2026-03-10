<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Virtigia Map Editor API',
    description: 'Token do autoryzacji wygenerujesz w panelu użytkownika: Profil -> Tokeny API.',
)]
class OpenApiSpec {}

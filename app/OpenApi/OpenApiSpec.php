<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Virtigia Map Editor API',
    description: 'Token do autoryzacji wygenerujesz w panelu użytkownika: Profil -> Tokeny API.',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'API Token',
)]
#[OA\SecurityScheme(
    securityScheme: 'worldHeader',
    type: 'apiKey',
    in: 'header',
    name: 'X-World',
    description: 'Wymagany nagłówek świata. Dostępne wartości: retro, classic, legacy, demo.',
)]
class OpenApiSpec {}

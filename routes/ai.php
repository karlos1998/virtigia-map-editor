<?php

use App\Http\Middleware\AuthenticateMcpWithVirtigia;
use App\Mcp\Servers\VirtigiaContentServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::get('/.well-known/oauth-protected-resource/{path?}', function () {
    $authorizationServer = rtrim((string) (config('services.virtigia_page.url') ?: config('services.laravelpassport.host')), '/');

    return response()->json([
        'resource' => url('/mcp/virtigia'),
        'authorization_servers' => [$authorizationServer],
        'scopes_supported' => [(string) config('services.virtigia_mcp.auth_scope', 'mcp:use')],
    ]);
})->where('path', '.*')->name('mcp.oauth.protected-resource');

Mcp::web('/mcp/virtigia', VirtigiaContentServer::class)
    ->middleware(AuthenticateMcpWithVirtigia::class);

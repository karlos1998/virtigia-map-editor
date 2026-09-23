<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Mcp\VirtigiaMcpAuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMcpWithVirtigia
{
    public function __construct(
        private readonly VirtigiaMcpAuthService $authService,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->authService->authenticate((string) $request->bearerToken());

        if ($user === null) {
            return response()->json([
                'message' => 'Wymagane jest aktywne konto Virtigii z dostępem do edytora map.',
            ], 401, [
                'WWW-Authenticate' => 'Bearer realm="virtigia-mcp", scope="mcp:use", resource_metadata="'.route('mcp.oauth.protected-resource').'"',
            ]);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }
}

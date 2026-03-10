<?php

namespace App\Http\Middleware;

use App\Models\UserApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json([
                'message' => 'Brak tokenu API. Użyj nagłówka Authorization: Bearer {token}.',
            ], 401);
        }

        $token = UserApiToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->active()
            ->first();

        if (! $token || ! $token->user) {
            return response()->json([
                'message' => 'Token API jest nieprawidłowy, wygasł lub został unieważniony.',
            ], 401);
        }

        $token->update([
            'last_used_at' => now(),
        ]);

        Auth::setUser($token->user);

        $request->setUserResolver(static function () use ($token) {
            return $token->user;
        });

        return $next($request);
    }
}

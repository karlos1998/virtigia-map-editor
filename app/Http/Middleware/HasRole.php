<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $roleName = null): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || count($user->roles ?? []) === 0) {
            return to_route('locked');
        }

        if ($roleName !== null) {
            abort_unless($user->hasRole($roleName), 403);
        }

        return $next($request);
    }
}

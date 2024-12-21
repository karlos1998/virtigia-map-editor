<?php

namespace App\Http\Middleware;

use App\Models\DynamicModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDynamicModelConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

//        $connectionName = auth()->check() ? 'retro' : config('database.default');
        $connectionName = 'retro';

        DynamicModel::setGlobalConnection($connectionName);


        return $next($request);
    }
}

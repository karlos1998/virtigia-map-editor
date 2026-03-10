<?php

namespace App\Http\Middleware;

use App\Enums\WorldType;
use App\Models\DynamicModel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiWorldConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerWorld = $request->header('X-World');

        if ($headerWorld === null || $headerWorld === '') {
            return response()->json([
                'message' => 'Brak wymaganego nagłówka X-World.',
                'errors' => [
                    'X-World' => ['Nagłówek X-World jest wymagany. Dostępne wartości: retro, classic, legacy, demo.'],
                ],
            ], 422);
        }

        $world = strtolower((string) $headerWorld);

        if (! in_array($world, WorldType::getAll(), true)) {
            return response()->json([
                'message' => 'Niepoprawny świat.',
                'errors' => [
                    'X-World' => ['Dostępne wartości: retro, classic, legacy, demo.'],
                ],
            ], 422);
        }

        DynamicModel::setGlobalConnection($world);
        $request->attributes->set('world', $world);

        return $next($request);
    }
}

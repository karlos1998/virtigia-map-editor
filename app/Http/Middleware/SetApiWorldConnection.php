<?php

namespace App\Http\Middleware;

use App\Models\DynamicModel;
use App\Services\WorldTemplateConnectionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiWorldConnection
{
    public function __construct(private readonly WorldTemplateConnectionResolver $connectionResolver) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerWorld = $request->header('X-World');
        $availableWorlds = $this->connectionResolver->visibleSlugs();
        $availableWorldsLabel = implode(', ', $availableWorlds);

        if ($headerWorld === null || $headerWorld === '') {
            return response()->json([
                'message' => 'Brak wymaganego nagłówka X-World.',
                'errors' => [
                    'X-World' => ["Nagłówek X-World jest wymagany. Dostępne wartości: {$availableWorldsLabel}."],
                ],
            ], 422);
        }

        $world = strtolower((string) $headerWorld);

        if (! in_array($world, $availableWorlds, true)) {
            return response()->json([
                'message' => 'Niepoprawny świat.',
                'errors' => [
                    'X-World' => ["Dostępne wartości: {$availableWorldsLabel}."],
                ],
            ], 422);
        }

        $template = $this->connectionResolver->resolve($world);

        if ($template === null) {
            return response()->json([
                'message' => 'Niepoprawny świat.',
                'errors' => [
                    'X-World' => ["Dostępne wartości: {$availableWorldsLabel}."],
                ],
            ], 422);
        }

        $this->connectionResolver->registerTemplateConnection($template);
        DynamicModel::setGlobalConnection($template->connection_name);
        $request->attributes->set('world', $world);
        $request->attributes->set('world_template', $template);

        return $next($request);
    }
}

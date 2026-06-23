<?php

namespace App\Http\Middleware;

use App\Models\DynamicModel;
use App\Services\WorldTemplateConnectionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetDynamicModelConnection
{
    public function __construct(private readonly WorldTemplateConnectionResolver $connectionResolver) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $world = session('world');

        if (! $world) {
            return response()->redirectToRoute('login');
        }

        $template = $this->connectionResolver->resolve((string) $world);

        if ($template === null) {
            $request->session()->forget('world');

            return response()->redirectToRoute('login');
        }

        $this->connectionResolver->registerTemplateConnection($template);
        DynamicModel::setGlobalConnection($template->connection_name);
        $request->attributes->set('world_template', $template);

        return $next($request);
    }
}

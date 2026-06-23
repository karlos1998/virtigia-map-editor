<?php

use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Support\Facades\Auth;

if (! function_exists('get_current_world_template')) {
    function get_current_world_template(): string
    {
        return Auth::getSession()->get('world', app(WorldTemplateConnectionResolver::class)->defaultWorldSlug());
    }
}

if (! function_exists('get_current_world_template_connection')) {
    function get_current_world_template_connection(): string
    {
        $world = get_current_world_template();

        return app(WorldTemplateConnectionResolver::class)->connectionNameFor($world) ?? $world;
    }
}

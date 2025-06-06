<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\ActivityLogger;
use Illuminate\Support\Facades\Auth;

class ActivityLogServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add a tap to the activity logger to include the current world
        $this->app->extend(ActivityLogger::class, function ($service, $app) {
            $service->tap(function ($activity) {
                // Get the current world from the session, default to 'retro' if not set
                $world = Auth::check() ? Auth::getSession()->get('world', 'retro') : 'retro';

                // Set the world attribute on the activity model
                $activity->world = $world;
            });

            return $service;
        });
    }
}

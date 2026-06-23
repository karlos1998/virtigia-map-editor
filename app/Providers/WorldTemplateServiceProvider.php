<?php

namespace App\Providers;

use App\Services\WorldTemplateConnectionResolver;
use App\Services\WorldTemplateDatabaseService;
use Illuminate\Support\ServiceProvider;

class WorldTemplateServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(WorldTemplateConnectionResolver::class);
        $this->app->singleton(WorldTemplateDatabaseService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->make(WorldTemplateConnectionResolver::class)->registerConfiguredConnections();
    }
}

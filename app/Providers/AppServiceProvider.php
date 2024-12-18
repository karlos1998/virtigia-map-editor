<?php

namespace App\Providers;

use App\Providers\Socialite\VirtigiaPageProvider;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        JsonResource::withoutWrapping();


        Socialite::extend('virtigia_page', function ($app) {
            $config = $app['config']['services.virtigia_page'];
            return Socialite::buildProvider(VirtigiaPageProvider::class, $config);
        });

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('laravelpassport', \SocialiteProviders\LaravelPassport\Provider::class);
        });
    }
}

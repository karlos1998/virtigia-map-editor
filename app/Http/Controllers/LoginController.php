<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OAuthPermissionPayload;
use App\Services\WorldTemplateConnectionResolver;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Login', []);
    }

    public function redirectToLogin()
    {
        return Socialite::driver('laravelpassport')->redirect();
    }

    public function handleCallback(OAuthPermissionPayload $permissionPayload, WorldTemplateConnectionResolver $connectionResolver)
    {
        $oauthUser = Socialite::driver('laravelpassport')->user();
        $payload = (array) $oauthUser->user;

        if (! $permissionPayload->hasMapEditorAccess($payload)) {
            return to_route('login')
                ->withErrors(['auth' => 'Nie masz uprawnienia do edytora map.']);
        }

        $user = User::updateOrCreate([
            'id' => $oauthUser->getId(),
        ], $payload);

        Auth::login($user);

        Auth::getSession()->put('world', $connectionResolver->defaultWorldSlug());

        return to_route('dashboard');
    }

    public function logout()
    {
        Auth::logout();

        return to_route('home');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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

    public function handleCallback()
    {
        $user = Socialite::driver('laravelpassport')->user();

        $user = User::updateOrCreate([
            'id' => $user->getId(),
        ], $user->user);

        Auth::login($user);

        return to_route('dashboard');
    }

    public function logout() {
        Auth::logout();
        return to_route('home');
    }
}

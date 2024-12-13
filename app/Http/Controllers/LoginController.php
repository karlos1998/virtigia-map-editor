<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function redirectToLogin()
    {
        return Socialite::driver('laravelpassport')->redirect();
    }

    public function handleCallback()
    {
        $user = Socialite::driver('laravelpassport')->user();

        dd($user);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\SwitchWorldRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorldController extends Controller
{
    public function switchWorld(SwitchWorldRequest $request): RedirectResponse
    {
        $world = $request->validated('world');

        Auth::getSession()->put('world', $world);

        return redirect()->back();
    }
}

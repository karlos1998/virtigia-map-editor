<?php

namespace App\Http\Controllers;

use App\Enums\WorldType;
use App\Http\Requests\SwitchWorldRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class WorldController extends Controller
{
    /**
     * Switch to the specified world.
     *
     * @param SwitchWorldRequest $request
     * @return RedirectResponse
     */
    public function switchWorld(SwitchWorldRequest $request): RedirectResponse
    {
        $world = $request->validated('world');

        // Set the world in the session
        Auth::getSession()->put("world", $world);

        return redirect()->back();
    }
}

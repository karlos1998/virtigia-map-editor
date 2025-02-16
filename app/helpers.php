<?php

use Illuminate\Support\Facades\Auth;

if (! function_exists('get_current_world_template')) {
    function get_current_world_template() {
        return Auth::getSession()->get("world", "default");
    }
}

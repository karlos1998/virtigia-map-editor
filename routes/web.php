<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    $mapModel = (new \App\Models\Map())->setConnectionName('retro');
    $maps = $mapModel->newQuery()->get();
    dd($maps);
});

Route::get('/', \App\Http\Controllers\DashboardController::class);

Route::get('login', [LoginController::class, 'redirectToLogin']);
Route::get('callback', [LoginController::class, 'handleCallback']);

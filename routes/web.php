<?php

use App\Http\Controllers\BaseItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NpcController;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    $mapModel = (new \App\Models\Map())->setConnectionName('retro');
    $maps = $mapModel->newQuery()->get();
    dd($maps);
});

Route::get('/', \App\Http\Controllers\DashboardController::class);

Route::get('login', [LoginController::class, 'redirectToLogin']);
Route::get('callback', [LoginController::class, 'handleCallback']);



Route::get('maps', [MapController::class, 'index'])->name('maps.index');
Route::get('maps/{map}', [MapController::class, 'show'])->name('maps.show');

Route::get('base-items', [BaseItemController::class, 'index'])->name('base-items.index');

Route::get('npcs', [NpcController::class, 'index'])->name('npcs.index');

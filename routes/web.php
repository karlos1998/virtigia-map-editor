<?php

use App\Http\Controllers\BaseItemController;
use App\Http\Controllers\BaseNpcController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DialogController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NpcController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\RemoveWorldTemplateNameFromRouteParameters;
use App\Http\Middleware\SetDynamicModelConnection;
use Illuminate\Support\Facades\Route;

//Route::get('/test', function () {
//    $mapModel = (new \App\Models\Map())->setConnectionName('retro');
//    $maps = $mapModel->newQuery()->get();
//    dd($maps);
//});
Route::get('/', function () {
//    dd('widok glowny w budowie');
    return to_route('dashboard');
})->name('home');

Route::get('login', [LoginController::class, 'show'])->name('login');
Route::get('login/redirect', [LoginController::class, 'redirectToLogin'])->name('login.redirect');
Route::get('login/callback', [LoginController::class, 'handleCallback']);

Route::middleware(['auth'])->group(function () {

    Route::get('locked', [DashboardController::class, 'locked'])->name('locked');

    Route::post('logout', [LoginController::class, 'logout'])->name('logout');

    Route::middleware(\App\Http\Middleware\HasRole::class)->group(function () {

        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route
//        ->where(['retro', 'classic'])
            ::middleware([
                SetDynamicModelConnection::class,
                //RemoveWorldTemplateNameFromRouteParameters::class //todo
            ])
            ->group(function () {
                Route::get('dialogs', [DialogController::class, 'index'])->name('dialogs.index');
                Route::get('dialogs/{dialog}', [DialogController::class, 'show'])->name('dialogs.show');
                Route::post('dialogs/{dialog}/nodes', [DialogController::class, 'addNode'])->name('dialogs.nodes.store');
                Route::post('dialogs/{dialog}/nodes/{dialogNode}', [DialogController::class, 'moveNode'])->name('dialogs.nodes.move');
                Route::post('dialogs/{dialog}/edges', [DialogController::class, 'addEdge'])->name('dialogs.edges.store');
                Route::post('dialogs/{dialog}/nodes/{dialogNode}/options', [DialogController::class, 'addOption'])->name('dialogs.nodes.options.store');

                Route::get('maps/search', [MapController::class, 'search'])->name('maps.search');
                Route::get('maps', [MapController::class, 'index'])->name('maps.index');
                Route::get('maps/{map}', [MapController::class, 'show'])->name('maps.show');

                Route::get('base-items', [BaseItemController::class, 'index'])->name('base-items.index');
                Route::get('base-items/search', [BaseItemController::class, 'search'])->name('base-items.search');

                Route::get('npcs', [NpcController::class, 'index'])->name('npcs.index');
                Route::get('npcs/{npc}', [NpcController::class, 'show'])->name('npcs.show');

                Route::get('shops', [ShopController::class, 'index'])->name('shops.index');
                Route::get('shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
                Route::post('shops/{shop}/items', [ShopController::class, 'addItem'])->name('shops.items.store');
                Route::delete('shops/{shop}/items/{position}', [ShopController::class, 'destroyItem'])->name('shops.items.destroy');

                Route::get('base-npcs', [BaseNpcController::class, 'index'])->name('base-npcs.index');
                Route::get('base-npcs/{baseNpc}', [BaseNpcController::class, 'show'])->name('base-npcs.show');
            });
    });


});

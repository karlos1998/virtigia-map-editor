<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BaseItemController;
use App\Http\Controllers\BaseNpcController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DialogController;
use App\Http\Controllers\DoorController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NpcController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\RemoveWorldTemplateNameFromRouteParameters;
use App\Http\Middleware\SetDynamicModelConnection;
use App\Jobs\FindNearestRespForMap;
use App\Models\DynamicModel;
use App\Models\Npc;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

        Route
//        ->where(['retro', 'classic'])
            ::middleware([
                SetDynamicModelConnection::class,
                //RemoveWorldTemplateNameFromRouteParameters::class //todo
            ])
            ->group(function () {

                Route::get('/dashboard', DashboardController::class)->name('dashboard');

                //todo - trzbea to pogrupowac...
                Route::get('dialogs', [DialogController::class, 'index'])->name('dialogs.index');
                Route::post('dialogs', [DialogController::class, 'store'])->name('dialogs.store');
                Route::get('dialogs/search', [DialogController::class, 'search'])->name('dialogs.search');
                Route::get('dialogs/{dialog}', [DialogController::class, 'show'])->name('dialogs.show');
                Route::post('dialogs/{dialog}/nodes', [DialogController::class, 'addNode'])->name('dialogs.nodes.store');
                Route::patch('dialogs/{dialog}/nodes/{dialogNode}', [DialogController::class, 'updateNode'])->name('dialogs.nodes.update');
                Route::delete('dialogs/{dialog}/nodes/{dialogNode}', [DialogController::class, 'destroyNode'])->name('dialogs.nodes.destroy');
                Route::post('dialogs/{dialog}/nodes/{dialogNode}', [DialogController::class, 'moveNode'])->name('dialogs.nodes.move');
                Route::post('dialogs/{dialog}/edges', [DialogController::class, 'addEdge'])->name('dialogs.edges.store');
                Route::delete('dialogs/{dialog}/edges/{dialogEdge}', [DialogController::class, 'destroyEdge'])->name('dialogs.edges.destroy');
                Route::post('dialogs/{dialog}/nodes/{dialogNode}/options', [DialogController::class, 'addOption'])->name('dialogs.nodes.options.store');
                Route::patch('dialogs/{dialog}/nodes/{dialogNode}/options/{dialogNodeOption}', [DialogController::class, 'updateOption'])->name('dialogs.nodes.options.update');
                Route::delete('dialogs/{dialog}/nodes/{dialogNode}/options/{dialogNodeOption}', [DialogController::class, 'destroyOption'])->name('dialogs.nodes.options.destroy');
                Route::patch('dialog/{dialog}/nodes/{dialogNode}/action', [DialogController::class, 'updateAction'])->name('dialogs.nodes.action.update');
                Route::put('dialog/{dialog}/nodes/{dialogNode}/shop', [DialogController::class, 'assignShop'])->name('dialogs.nodes.shop.assign');

                Route::get('maps/create', [MapController::class, 'create'])->name('maps.create');
                Route::post('maps', [MapController::class, 'store'])->name('maps.store');

                Route::get('maps/search', [MapController::class, 'search'])->name('maps.search');
                Route::get('maps', [MapController::class, 'index'])->name('maps.index');
                Route::get('maps/{map}', [MapController::class, 'show'])->name('maps.show');
                Route::patch('maps/{map}/cols', [MapController::class, 'updateCol'])->name('maps.update.col');
                Route::patch('maps/{map}/water', [MapController::class, 'updateWater'])->name('maps.update.water');
                Route::patch('maps/{map}/name', [MapController::class, 'updateName'])->name('maps.update.name');
                Route::patch('maps/{map}/pvp', [MapController::class, 'updatePvp'])->name('maps.update.pvp');
                Route::patch('maps/{map}/respawn-point', [MapController::class, 'updateRespawnPoint'])->name('maps.update.respawn-point');

                Route::get('base-items', [BaseItemController::class, 'index'])->name('base-items.index');
                Route::post('base-items/{baseItem}/copy', [BaseItemController::class, 'copy'])->name('base-items.copy');
                Route::delete('base-items/{baseItem}', [BaseItemController::class, 'delete'])->name('base-items.delete');
                Route::patch('base-items/{baseItem}', [BaseItemController::class, 'update'])->name('base-items.update');
                Route::patch('base-items/{baseItem}/image', [BaseItemController::class, 'updateImage'])->name('base-items.image.update');
                Route::get('base-items/search', [BaseItemController::class, 'search'])->name('base-items.search');
                Route::get('base-items/{baseItem}', [BaseItemController::class, 'show'])->name('base-items.show');
                Route::get('base-items/{baseItem}/edit', [BaseItemController::class, 'edit'])->name('base-items.edit');
                Route::patch('base-items/{baseItem}/attributes', [BaseItemController::class, 'updateAttributes'])->name('base-items.attributes.update');

                Route::get('npcs', [NpcController::class, 'index'])->name('npcs.index');
                Route::get('npcs/search-hero', [NpcController::class, 'searchHero'])->name('npcs.search-hero');
                Route::get('npcs/{npc}', [NpcController::class, 'show'])->name('npcs.show');
                Route::patch('npcs/{npc}', [NpcController::class, 'update'])->name('npcs.update');
                Route::patch('npcs/{npc}/locations/{npcLocation}', [NpcController::class, 'updateLocation'])->name('npcs.update.location');

                Route::post('npcs', [NpcController::class, 'store'])->name('npcs.store');
                Route::post('npcs/{npc}/locations', [NpcController::class, 'storeLocation'])->name('npcs.locations.store');
                Route::delete('npcs/{npc}/locations/{npcLocation}', [NpcController::class, 'destroyLocation'])->name('npcs.locations.destroy');

                Route::delete('npcs/{npc}/group-detach', [NpcController::class, 'detachGroup'])->name('npcs.group.detach');
                Route::post('npcs/group/add', [NpcController::class, 'addToGroup'])->name('npcs.group.add');
                Route::post('npcs/group/create', [NpcController::class, 'createGroup'])->name('npcs.group.create');

                Route::get('shops', [ShopController::class, 'index'])->name('shops.index');
                Route::post('shops', [ShopController::class, 'store'])->name('shops.store');
                Route::get('shops/search', [ShopController::class, 'search'])->name('shops.search');
                Route::get('shops/{shop}', [ShopController::class, 'show'])->name('shops.show');
                Route::post('shops/{shop}/items', [ShopController::class, 'addItem'])->name('shops.items.store');
                Route::delete('shops/{shop}/items/{position}', [ShopController::class, 'destroyItem'])->name('shops.items.destroy');

                /**
                Generowanie template z npc dla forum
                 */
                Route::get('base-npcs/forum-generator', [BaseNpcController::class, 'forumGenerator'])->name('base-npcs.forum-generator');

                Route::get('base-npcs', [BaseNpcController::class, 'index'])->name('base-npcs.index');
                Route::get('base-npcs/create-advanced', [BaseNpcController::class, 'createAdvanced'])->name('base-npcs.create-advanced');
                Route::get('base-npcs/create', [BaseNpcController::class, 'create'])->name('base-npcs.create');
                Route::get('base-npcs/search', [BaseNpcController::class, 'search'])->name('base-npcs.search');
                Route::get('base-npcs/search-hero', [BaseNpcController::class, 'searchHero'])->name('base-npcs.search-hero');

                Route::get('base-npcs/{baseNpc}', [BaseNpcController::class, 'show'])->name('base-npcs.show');

                Route::patch('base-npcs/{baseNpc}/image', [BaseNpcController::class, 'updateImage'])->name('base-npcs.image.update');

                Route::post('base-npcs/{baseNpc}/loots', [BaseNpcController::class, 'attachLoot'])->name('base-npcs.loots.attach');
                Route::post('base-npcs/{baseNpc}/loots/from-base-npc', [BaseNpcController::class, 'attachLootsFromBaseNpc'])->name('base-npcs.loots.attach-from-base-npc');
                Route::delete('base-npcs/{baseNpc}/loots/{loot}', [BaseNpcController::class, 'detachLoot'])->name('base-npcs.loots.detach');
                Route::get('web-api/base-npcs', [BaseNpcController::class, 'indexJson'])->name('web-api.base-npcs.index');
                Route::post('base-npcs/advanced', [BaseNpcController::class, 'store'])->name('base-npcs.store');
                Route::post('base-npcs', [BaseNpcController::class, 'storeSimple'])->name('base-npcs.store-simple');
                Route::delete('base-npcs/{baseNpc}', [BaseNpcController::class, 'destroy'])->name('base-npcs.destroy');
                Route::patch('base-npcs/{baseNpc}', [BaseNpcController::class, 'update'])->name('base-npcs.update');

                Route::get('assets/base-npcs/search', [AssetController::class, 'searchNpcs'])->name('assets.base-npcs.search');


                Route::post('doors', [DoorController::class, 'store'])->name('doors.store');
                Route::patch('doors/{door}/move', [DoorController::class, 'move'])->name('doors.move');
                Route::patch('doors/{door}/level', [DoorController::class, 'updateLevel'])->name('doors.level.update');
                Route::patch('doors/{door}/required-item', [DoorController::class, 'updateRequiredItem'])->name('doors.required-item.update');
                Route::delete('doors/{door}', [DoorController::class, 'destroy'])->name('doors.destroy');


            });

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');

        Route::get('/s3/{path}', function ($path) {

            if (!Storage::disk('s3')->exists($path)) {
                abort(404);
            }

            $file = Storage::disk('s3')->get($path);
            $mimeType = Storage::disk('s3')->mimeType($path);

            return Response::make($file, 200, ['Content-Type' => $mimeType]);
        })->where('path', '.*');
    });

    Route::get('user-me', function() {
        dd(auth()->user());
    });

});

Route::get('debug-stats/characters', fn() => \Inertia\Inertia::render('DebugStats/Characters'));
Route::get('debug-stats/base-npcs', fn() => \Inertia\Inertia::render('DebugStats/BaseNpcs'));
Route::get('debug-stats/api/characters', function() {
    $request = Http::get('https://virtigia-engine.letscode.it/debug-stats/characters?profession=' . request()->get('profession'));
    return $request->json();
});
Route::get('debug-stats/api/base-npcs', function() {
    $request = Http::get('https://mbp-karol-java.letscode.it/debug-stats/base-npcs?rank=' . request()->get('rank') . '&profession=' . request()->get('profession'));
    return $request->json();
});

Route::get('go-to-test', function() {
    Auth::getSession()->put("world", "test");
    return response()->redirectTo('/');
});

Route::get('go-to-classic', function() {
    Auth::getSession()->put("world", "classic");
    return response()->redirectTo('/');
});

Route::get('npcs-without-locations', function(){
    Npc::setGlobalConnection('retro');
   $output = Npc::doesntHave('locations')->get();
   dd($output->map(fn($npc) => [$npc->id, $npc->base->name])->toArray());
//    Npc::doesntHave('locations')->delete();
});

//Route::get('debug-api/maps', function() {
//    \App\Models\Map::setGlobalConnection('retro');
//    return response()->json(\App\Models\Map::get(['id', 'name']));
//});
//
//Route::get('debug/path', function() {
//    DynamicModel::setGlobalConnection('retro');
//    foreach(\App\Models\Map::whereNull('respawn_point_id')->get() as $map) {
//        dispatch(new FindNearestRespForMap($map));
//    }
////    dispatch_sync(new FindNearestRespForMap(\App\Models\Map::find(10)));
//});

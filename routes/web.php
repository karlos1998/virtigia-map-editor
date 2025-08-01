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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestController;
use App\Http\Controllers\QuestStepController;
use App\Http\Controllers\ShopController;
use App\Http\Middleware\RemoveWorldTemplateNameFromRouteParameters;
use App\Http\Middleware\SetDynamicModelConnection;;
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

    Route::get('profile', [ProfileController::class, 'show'])->name('profile');

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
                Route::patch('dialogs/{dialog}', [DialogController::class, 'update'])->name('dialogs.update');
                Route::post('dialogs/{dialog}/copy', [DialogController::class, 'copyDialog'])->name('dialogs.copy');
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
                Route::patch('dialogs/{dialog}/nodes/{dialogNode}/start-edges', [DialogController::class, 'updateStartNodeEdges'])->name('dialogs.nodes.start-edges.update');
                Route::post('dialogs/{dialog}/nodes/{dialogNode}/copy', [DialogController::class, 'copyNode'])->name('dialogs.nodes.copy');

                Route::get('maps/create', [MapController::class, 'create'])->name('maps.create');
                Route::post('maps', [MapController::class, 'store'])->name('maps.store');

                Route::get('maps/search', [MapController::class, 'search'])->name('maps.search');
                Route::get('maps', [MapController::class, 'index'])->name('maps.index');
                Route::get('maps/{map}', [MapController::class, 'show'])->name('maps.show');

                Route::get('respawn-points', [\App\Http\Controllers\RespawnPointController::class, 'index'])->name('respawn-points.index');
                Route::post('respawn-points', [\App\Http\Controllers\RespawnPointController::class, 'store'])->name('respawn-points.store');
                Route::patch('respawn-points/{respawnPoint}', [\App\Http\Controllers\RespawnPointController::class, 'update'])->name('respawn-points.update');
                Route::delete('respawn-points/{respawnPoint}', [\App\Http\Controllers\RespawnPointController::class, 'destroy'])->name('respawn-points.destroy');

                // Spawn Points routes
                Route::get('spawn-points', [\App\Http\Controllers\SpawnPointController::class, 'index'])->name('spawn-points.index');
                Route::post('spawn-points', [\App\Http\Controllers\SpawnPointController::class, 'store'])->name('spawn-points.store');
                Route::patch('spawn-points/{spawnPoint}', [\App\Http\Controllers\SpawnPointController::class, 'update'])->name('spawn-points.update');
                Route::post('spawn-points/set-default-for-missing', [\App\Http\Controllers\SpawnPointController::class, 'setDefaultForMissing'])->name('spawn-points.set-default-for-missing');

                Route::get('world-info', [\App\Http\Controllers\WorldInfoController::class, 'index'])->name('world-info.index');
                Route::patch('maps/{map}/cols', [MapController::class, 'updateCol'])->name('maps.update.col');
                Route::patch('maps/{map}/clear-collisions', [MapController::class, 'clearCollisions'])->name('maps.clear.collisions');
                Route::patch('maps/{map}/water', [MapController::class, 'updateWater'])->name('maps.update.water');
                Route::patch('maps/{map}/name', [MapController::class, 'updateName'])->name('maps.update.name');
                Route::patch('maps/{map}/pvp', [MapController::class, 'updatePvp'])->name('maps.update.pvp');
                Route::patch('maps/{map}/respawn-point', [MapController::class, 'updateRespawnPoint'])->name('maps.update.respawn-point');
                Route::post('maps/{map}/image', [MapController::class, 'updateImage'])->name('maps.update.image');
                Route::delete('maps/{map}', [MapController::class, 'destroy'])->name('maps.destroy');
                Route::post('maps/{map}/copy', [MapController::class, 'copy'])->name('maps.copy');

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
                Route::post('shops/{shop}/toggle-binds-items-permanently', [ShopController::class, 'toggleBindsItemsPermanently'])->name('shops.toggle-binds-items-permanently');

                // Quest routes
                Route::get('quests', [QuestController::class, 'index'])->name('quests.index');
                Route::post('quests', [QuestController::class, 'store'])->name('quests.store');
                Route::get('quests/search', [QuestController::class, 'search'])->name('quests.search');
                Route::get('quests/{quest}', [QuestController::class, 'show'])->name('quests.show');
                Route::get('quests/{quest}/steps', [QuestController::class, 'getSteps'])->name('quests.steps');
                Route::patch('quests/{quest}', [QuestController::class, 'update'])->name('quests.update');
                Route::delete('quests/{quest}', [QuestController::class, 'destroy'])->name('quests.destroy');

                // Quest Step routes
                Route::post('quests/{quest}/steps', [QuestStepController::class, 'store'])->name('quests.steps.store');
                Route::patch('quests/{quest}/steps/{step}', [QuestStepController::class, 'update'])->name('quests.steps.update');
                Route::delete('quests/{quest}/steps/{step}', [QuestStepController::class, 'destroy'])->name('quests.steps.destroy');
                Route::get('quest-steps/{step}', [QuestStepController::class, 'show'])->name('quest.steps.show');

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

                // Routes for merging base NPCs
                Route::post('base-npcs/{sourceBaseNpc}/transfer-npcs', [BaseNpcController::class, 'transferNpcs'])->name('base-npcs.transfer-npcs');
                Route::post('base-npcs/{baseNpc}/convert-to-layer', [BaseNpcController::class, 'convertToLayer'])->name('base-npcs.convert-to-layer');
                Route::post('base-npcs/{baseNpc}/revert-from-layer', [BaseNpcController::class, 'revertFromLayer'])->name('base-npcs.revert-from-layer');

                Route::get('assets/base-npcs/search', [AssetController::class, 'searchNpcs'])->name('assets.base-npcs.search');


                Route::post('doors', [DoorController::class, 'store'])->name('doors.store');
                Route::patch('doors/{door}/move', [DoorController::class, 'move'])->name('doors.move');
                Route::patch('doors/{door}/level', [DoorController::class, 'updateLevel'])->name('doors.level.update');
                Route::patch('doors/{door}/required-item', [DoorController::class, 'updateRequiredItem'])->name('doors.required-item.update');
                Route::delete('doors/{door}', [DoorController::class, 'destroy'])->name('doors.destroy');

                Route::get('titan-doors', [DoorController::class, 'titanDoors'])->name('titan-doors.index');
                Route::post('doors/update-level-restrictions', [DoorController::class, 'updateLevelRestrictions'])->name('doors.update-level-restrictions');

            });

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('users', [\App\Http\Controllers\UsersController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [\App\Http\Controllers\UsersController::class, 'show'])->name('users.show');

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
    $request = Http::get('https://virtigia-engine.letscode.it/debug-stats/base-npcs?rank=' . request()->get('rank') . '&profession=' . request()->get('profession'));
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

Route::post('switch-world', [\App\Http\Controllers\WorldController::class, 'switchWorld'])->name('switch-world');

Route::get('search', [\App\Http\Controllers\SearchController::class, 'search'])->name('search');

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

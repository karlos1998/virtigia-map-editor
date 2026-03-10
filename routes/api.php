<?php

use App\Http\Controllers\Api\V1\BaseNpcController as ApiBaseNpcController;
use App\Http\Controllers\Api\V1\MapController as ApiMapController;
use App\Http\Controllers\Api\V1\NpcController as ApiNpcController;
use App\Http\Controllers\Api\V1\ProfileController as ApiProfileController;
use App\Http\Controllers\ApiAttributePointController;
use App\Http\Middleware\AuthenticateWithApiToken;
use App\Http\Middleware\SetApiWorldConnection;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([AuthenticateWithApiToken::class, SetApiWorldConnection::class])
    ->group(function () {
        Route::get('/profile', [ApiProfileController::class, 'show'])->name('profile.show');

        Route::get('/maps', [ApiMapController::class, 'index'])->name('maps.index');
        Route::get('/maps/{mapId}', [ApiMapController::class, 'show'])->name('maps.show');
        Route::patch('/maps/{mapId}', [ApiMapController::class, 'update'])->name('maps.update');

        Route::get('/base-npcs', [ApiBaseNpcController::class, 'index'])->name('base-npcs.index');
        Route::get('/base-npcs/{baseNpcId}', [ApiBaseNpcController::class, 'show'])->name('base-npcs.show');

        Route::get('/npcs', [ApiNpcController::class, 'index'])->name('npcs.index');
        Route::get('/npcs/{npcId}', [ApiNpcController::class, 'show'])->name('npcs.show');
        Route::post('/npcs', [ApiNpcController::class, 'store'])->name('npcs.store');
        Route::patch('/npcs/{npcId}', [ApiNpcController::class, 'update'])->name('npcs.update');
        Route::delete('/npcs/{npcId}', [ApiNpcController::class, 'destroy'])->name('npcs.destroy');

        Route::get('/npcs/{npcId}/locations', [ApiNpcController::class, 'indexLocations'])->name('npcs.locations.index');
        Route::post('/npcs/{npcId}/locations', [ApiNpcController::class, 'storeLocation'])->name('npcs.locations.store');
        Route::delete('/npcs/{npcId}/locations/{locationId}', [ApiNpcController::class, 'destroyLocation'])->name('npcs.locations.destroy');
    });

/*
|--------------------------------------------------------------------------
| BaseItem API Routes
|--------------------------------------------------------------------------
|
| These routes handle attribute points calculation and scaling for items
| by proxying requests to external API services.
|
*/

Route::middleware(['web', 'auth'])->prefix('base-items')->name('api.base-items.')->group(function () {

    /**
     * Get available attribute points from external API
     *
     * Returns list of available attribute points that can be assigned to items,
     * including both regular attributes and manual attribute points.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    Route::get('/attribute-points', [ApiAttributePointController::class, 'getAttributePoints'])
        ->name('attribute-points');

    /**
     * Calculate scaled attributes based on item parameters and attribute points
     *
     * Accepts item parameters (level, category, rarity, professions) and attribute
     * point values, then returns calculated scaled attributes from external API.
     *
     * Query Parameters:
     * - lvl: Item level requirement
     * - itemCategory: Item category (armors, weapons, etc.)
     * - rarity: Item rarity (common, unique, etc.)
     * - itemProfessions: Required professions (comma-separated)
     * - {attributeName}: Attribute point values (criticalChance, armor, etc.)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    Route::get('/scale-attributes', [ApiAttributePointController::class, 'getScaleAttributes'])
        ->name('scale-attributes');

});

/*
|--------------------------------------------------------------------------
| Development & Testing Routes
|--------------------------------------------------------------------------
|
| Routes for testing and development purposes.
|
*/

if (app()->environment(['local', 'development'])) {
    /**
     * Test route to verify API routes are working
     */
    Route::get('/test', function () {
        return response()->json([
            'message' => 'API routes are working!',
            'timestamp' => now(),
        ]);
    })->name('api.test');
}

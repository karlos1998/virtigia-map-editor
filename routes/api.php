<?php

use App\Services\BaseItemService;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
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
    Route::get('/attribute-points', function (BaseItemService $baseItemService) {
        try {
            $attributePoints = $baseItemService->getAttributePoints();
            return response()->json($attributePoints);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('attribute-points');

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
    Route::get('/scale-attributes', function (Request $request, BaseItemService $baseItemService) {
        try {
            $parameters = $request->all();
            $scaledAttributes = $baseItemService->getScaleAttributes($parameters);
            return response()->json($scaledAttributes);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('scale-attributes');

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

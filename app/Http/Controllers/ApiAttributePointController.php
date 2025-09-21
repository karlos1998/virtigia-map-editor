<?php

namespace App\Http\Controllers;

use App\Services\ApiAttributePointService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * API Controller for handling attribute points operations
 *
 * This controller manages communication with external APIs for:
 * - Fetching available attribute points
 * - Calculating scaled attributes based on item parameters
 */
class ApiAttributePointController extends Controller
{
    public function __construct(
        private readonly ApiAttributePointService $apiAttributePointService
    )
    {
    }

    /**
     * Get available attribute points from external API
     *
     * Returns list of available attribute points that can be assigned to items,
     * including both regular attributes and manual attribute points.
     *
     * @return JsonResponse
     */
    public function getAttributePoints(): JsonResponse
    {
        try {
            $attributePoints = $this->apiAttributePointService->getAttributePoints();

            return response()->json($attributePoints);

        } catch (\Exception $e) {
            Log::error('Failed to get attribute points', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
     * @param Request $request
     * @return JsonResponse
     */
    public function getScaleAttributes(Request $request): JsonResponse
    {
        try {
            $parameters = $request->all();
            $scaledAttributes = $this->apiAttributePointService->getScaleAttributes($parameters);

            return response()->json($scaledAttributes);

        } catch (\Exception $e) {
            Log::error('Failed to get scaled attributes', [
                'parameters' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
